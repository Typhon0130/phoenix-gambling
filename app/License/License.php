<?php namespace App\License;

use App\Games\Kernel\ThirdParty\Phoenix\PhoenixGame;
use App\Models\Settings;
use App\Models\User;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class License {

  public function isValid(): bool {
    return $this->get() != null;
  }

  public function getKey(): string {
    return Settings::get('[License] Key', '-');
  }

  public function get(): ?array {
    try {
      if(Cache::has('license:info'))
        return Cache::get('license:info');

      $data = json_decode((new PhoenixGame())->curl('https://phoenix-gambling.com/api/license/info', [
        'key' => $this->getKey(),
        'ip' => User::getServerIp()
      ]), true);

      //Log::info($data);
      $isValid = isset($data['apiKey']);

      Cache::put('license:info', $isValid ? $data : null, now()->addMinutes(30));

      if($isValid) return $data;
      return null;
    } catch (\Exception $e) {
      Log::error($e);
      return null;
    }
  }

  public function getPhoenixFeatures(): array {
    if(Cache::has('license:phoenixFeatures'))
      return Cache::get('license:phoenixFeatures');

    $data = json_decode(file_get_contents("https://phoenix-gambling.com/api/license/features"), true);
    Cache::put('license:phoenixFeatures', $data, now()->addMinutes(30));
    return $data;
  }

  public function phoenixGamesApiKey(): string {
    return $this->get()['apiKey'];
  }

  public function maxWebsites(): int {
    return $this->get()['maxWebsites'];
  }

  public function features(): array {
    return isset($this->get()['features']) ? $this->get()['features'] : [];
  }

  public function hasFeature($id): bool {
    foreach ($this->features() as $feature)
      if($feature['id'] === $id) return true;
    return false;
  }

  public function whitelist(): array {
    return $this->get()['whitelist'];
  }

}
