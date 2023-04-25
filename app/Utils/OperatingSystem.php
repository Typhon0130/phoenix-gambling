<?php namespace App\Utils;

class OperatingSystem {

  public static function isWindows(): bool {
    return str_starts_with(strtoupper(PHP_OS), 'WIN');
  }

}
