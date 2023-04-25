<?php namespace App\Utils;

class Demo {

  public static function isDemo(bool $ignoreAuth = false): bool {
    $user = $ignoreAuth ? null : auth('sanctum')->user();
    if(!env('VITE_IS_DEMO')) return false;
    return $user == null || !$user->isDemoLimitationsIgnored;
  }

}
