<?php namespace App\Updater\Server;

use App\Games\Kernel\ThirdParty\Phoenix\PhoenixGame;
use App\License\License;

class PhoenixUpdaterServer extends UpdaterServer {

  public function manifest(): array {
    return json_decode((new PhoenixGame())->curl('https://phoenix-gambling.com/license/download/manifest/' . (new License())->getKey()), true);
  }

  public function download(string $version): mixed {
    return (new PhoenixGame())->curl('https://phoenix-gambling.com/license/download/patchRaw/' . $version . '/' . (new License())->getKey());
  }

  public function changelog(string $version): mixed {
    return (new PhoenixGame())->curl('https://phoenix-gambling.com/license/download/changelog/' . $version . '/' . (new License())->getKey());
  }

}
