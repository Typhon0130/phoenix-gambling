<?php namespace App\License\Plugin\Local;

use App\License\Plugin\Plugin;

abstract class LocalPlugin extends Plugin {

  public function version(): string {
    return json_decode(file_get_contents(base_path('@web/package.json')), true)['version'];
  }

}
