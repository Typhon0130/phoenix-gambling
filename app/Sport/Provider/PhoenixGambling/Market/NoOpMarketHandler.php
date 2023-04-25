<?php namespace App\Sport\Provider\PhoenixGambling\Market;

use App\Sport\Provider\SportGameSnapshot;
use App\Sport\Provider\SportMarketHandler;
use App\Sport\Provider\SportMarketTranslation;

class NoOpMarketHandler extends SportMarketHandler {

  public function supports(string $sportType): bool {
    return true;
  }

  function isHandling(string $market, string $runner): bool {
    return true;
  }

  function isWinner(string $runner, SportGameSnapshot $snapshot): string {
    return self::refund();
  }

  function translation(string $market, string $runner): SportMarketTranslation {
    return (new SportMarketTranslation())->same($market, $runner);
  }

}
