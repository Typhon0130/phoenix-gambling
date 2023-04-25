<?php namespace App\Sport\Provider\PhoenixGambling\Market\Esport;

use App\Sport\Provider\PhoenixGambling\PhoenixGamblingEsportMarketHandler;
use App\Sport\Provider\SportGameSnapshot;
use App\Sport\Provider\SportMarketTranslation;

class HomeWinsAtLeastOneMap extends PhoenixGamblingEsportMarketHandler {

  function isHandling(string $market, string $runner): bool {
    return $market === 'home wins at least one map';
  }

  function isWinner(string $runner, SportGameSnapshot $snapshot): string {
    if($runner === 'yes' && $this->esport($snapshot->id())->periodScores()->homeWonAtLeastOne()) return $this->win();
    if($runner === 'no' && !$this->esport($snapshot->id())->periodScores()->homeWonAtLeastOne()) return $this->win();
    return $this->lose();
  }

  function translation(string $market, string $runner): SportMarketTranslation {
    return (new SportMarketTranslation())->runner($runner)->market('sport.market.esport.homeWinsAtLeastOneMap');
  }

}
