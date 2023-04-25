<?php namespace App\Updater;

use App\Models\Settings;
use App\Updater\Server\PhoenixUpdaterServer;
use App\Updater\Server\UpdaterServer;
use App\Utils\APIResponse;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Artisan;
use Symfony\Component\Process\Process;

class Updater {

  private UpdaterServer $updaterServer;

  public function __construct() {
    $this->updaterServer = new PhoenixUpdaterServer();
  }

  public function server(): UpdaterServer {
    return $this->updaterServer;
  }

  public function update(): Response {
    Artisan::call('down');

    $complete = function() {
      Artisan::call('up');
    };

    $compileFrontend = function(string $type): array {
      $error = [];

      $process = new Process(['npm', 'run', 'build']);
      $process->setTimeout(null);
      $process->setWorkingDirectory(base_path($type));

      $code = $process->run(function ($type, $line) use (&$error) {
        if ($type == 'err') $error[] = $line;
      });

      return [
        'error' => $error,
        'code' => $code
      ];
    };

    $server = $this->server();
    $manifest = $server->manifest();
    $latest = $manifest['latest'];
    $current = $server->version();

    if(!$server->isHigher($latest, $current)) {
      $complete();

      if(Settings::get('[Installer] Built frontend at least once', 'false') === 'false') {
        $compileFrontend('@web');
        $compileFrontend('@admin');
        Settings::set('[Installer] Built frontend at least once', 'true');
      }

      return APIResponse::reject(0, 'Nothing to update');
    }

    $applyNextPatch = function($version) use ($server, $manifest, &$applyNextPatch) {
      file_put_contents(base_path('_update.patch'), $server->download($version));

      $error = [];

      $process = new Process(['git', 'apply', '_update.patch', '--whitespace=fix', '-v']);
      $process->setTimeout(null);
      $process->setWorkingDirectory(base_path());

      $code = $process->run((function ($type, $line) use (&$error) {
        if ($type == 'err') $error[] = $line;
      }));

      if($code !== 0)
        return [
          'status' => false,
          'version' => $version,
          'log' => join("\n", $error)
        ];

      if(isset($manifest['manifest'][$version]['next']))
        return $applyNextPatch($manifest['manifest'][$version]['next']);

      return [
        'status' => true
      ];
    };

    if(!isset($manifest['manifest'][$current]['next'])) {
      $complete();
      return APIResponse::reject(3, 'Manifest for current version is missing \"next\" parameter.');
    }

    $patchOutput = $applyNextPatch($manifest['manifest'][$current]['next']);

    if(!$patchOutput['status']) {
      $complete();
      return APIResponse::reject(4, "There was a merge conflict during installation of version \"" . $patchOutput['version'] . "\".\nThis usually happens because of the custom changes made in source code.\nRun this command: \"cd /var/www/html && git apply _update.patch --whitespace=fix -v\" and fix errors manually.\n\n\ngit log:\n\n" . $patchOutput['log']);
    }

    unlink(base_path('_update.patch'));

    Settings::set('[Installer] Built frontend at least once', 'true');

    $webOutput = $compileFrontend('@web');
    if($webOutput['code'] !== 0) {
      $complete();
      return APIResponse::reject(1, join('\n', $webOutput['error']));
    }

    $adminOutput = $compileFrontend('@admin');
    $complete();

    if($adminOutput['code'] !== 0) {
      return APIResponse::reject(2, join('\n', $adminOutput['error']));
    }

    return APIResponse::success();
  }

}
