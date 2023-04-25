<?php namespace App\Games\Kernel\ThirdParty;

use App\Games\Kernel\Game;
use App\Games\Kernel\ProvablyFairResult;
use App\Games\Kernel\ThirdParty\Phoenix\PhoenixGame;
use App\Games\Kernel\ThirdParty\Slotegrator\Slotegrator;
use App\License\License;
use App\Models\Settings;
use App\Utils\Exception\UnsupportedOperationException;

abstract class ThirdPartyGame extends Game {

  protected ?array $data;

  public function __construct(?array $data = null) {
    $this->data = $data;
  }

  abstract function provider(): string;

  /**
   * Creates array of ThirdPartyGame instances.
   * @return array of ThirdPartyGame instances based on it's provider
   */
  public abstract function createInstances(): array;

  public function result(ProvablyFairResult $result): array {
    throw new UnsupportedOperationException();
  }

  public static function providers(): array {
    $providers = [
      new PhoenixGame()
    ];

    if((new License())->hasFeature('externalSlots') && Settings::get('[Slotegrator] Enabled', 'false') === 'true')
      $providers = array_merge($providers, (new Slotegrator())->getProviders());

    return $providers;
  }

  public static function findProvider(string $id) {
    foreach (self::providers() as $provider) if ($provider->provider() === $id) return $provider;
    return null;
  }

}
