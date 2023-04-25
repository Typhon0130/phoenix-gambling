<?php namespace App\Utils;

class ViteHotReloadFile {

  public static function set($module) {
    $path = public_path($module . '_hot');
    $hotPath = public_path('hot');

    if(file_exists($path))
      file_put_contents($hotPath, file_get_contents($path));
    else if(!file_exists($path) && file_exists($hotPath)) unlink($hotPath);
  }

}
