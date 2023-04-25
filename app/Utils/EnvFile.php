<?php namespace App\Utils;

class EnvFile {

  public static function set(string $key, string $value): void {
    $path = app()->environmentFilePath();

    $escaped = preg_quote('='.env($key), '/');

    file_put_contents($path, preg_replace(
      "/^{$key}{$escaped}/m",
      "{$key}={$value}",
      file_get_contents($path)
    ));
  }

}
