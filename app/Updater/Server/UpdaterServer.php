<?php namespace App\Updater\Server;

abstract class UpdaterServer {

  public abstract function manifest(): array;

  public abstract function download(string $version): mixed;

  public abstract function changelog(string $version): mixed;

  public function version() {
    $packageJson = json_decode(file_get_contents(base_path('@web/package.json')), true);
    return $packageJson['version'];
  }

  public function isHigher($target, $compare): bool {
    $s = function($e) { return intval(str_replace(".", "", $e)); };
    return $s($target) > $s($compare);
  }

}
