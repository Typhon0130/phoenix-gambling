<?php namespace App\Sport\Provider\PhoenixGambling\Market\Sport;

use App\Sport\Provider\PhoenixGambling\PhoenixGamblingSportMarketHandler;
use App\Sport\Provider\SportGameSnapshot;
use App\Sport\Provider\SportMarketTranslation;

class ExactSets extends PhoenixGamblingSportMarketHandler {

  function isHandling(string $market, string $runner): bool {
    return $market === 'Exact sets';
  }

  function isWinner(string $runner, SportGameSnapshot $snapshot): string {
    if($this->getData($snapshot->id())->match()->periods() === intval($runner)) return $this->win();
    return $this->lose();
  }

  function translation(string $market, string $runner): SportMarketTranslation {
    return (new SportMarketTranslation())->market('sport.market.sport.exactSets')->runner($runner);
  }

}
