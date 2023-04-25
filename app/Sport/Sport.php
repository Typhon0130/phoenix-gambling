<?php namespace App\Sport;

use App\Games\Kernel\ThirdParty\Phoenix\PhoenixGame;
use App\Sport\Provider\SportLineProvider;
use Illuminate\Support\Facades\Cache;
use App\Sport\Provider\PhoenixGambling\PhoenixGamblingLine;
use Illuminate\Support\Facades\Log;

class Sport {

  public static function getLine(): SportLineProvider {
    return new PhoenixGamblingLine();
  }

  public static function cached(string $key, $callback, int $seconds = 0): array {
    if(Cache::has($key)) return Cache::get($key);
    $result = $callback();
    Cache::put($key, $result, now()->addSeconds($seconds));
    return $result;
  }

  public static function cachedRequest(string $url, $params = null, int $seconds = 0): string {
    $cacheKey = $url . ":" . $seconds;

    try {
      if(Cache::has($cacheKey)) return Cache::get($cacheKey);
      $result = (new PhoenixGame())->curl($url, $params);
      Cache::put($cacheKey, $result, now()->addSeconds($seconds));
      return $result;
    } catch (\Exception) {
      if (Cache::has($cacheKey)) return Cache::get($cacheKey);
      return "[]";
    }
  }

}
