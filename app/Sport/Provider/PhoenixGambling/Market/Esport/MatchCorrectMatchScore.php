<?php namespace App\Sport\Provider\PhoenixGambling\Market\Esport;

use App\Sport\Provider\PhoenixGambling\PhoenixGamblingEsportMarketHandler;
use App\Sport\Provider\SportGameSnapshot;
use App\Sport\Provider\SportMarketTranslation;

class MatchCorrectMatchScore extends PhoenixGamblingEsportMarketHandler {

  function isHandling(string $market, string $runner): bool {
    return $market === 'Correct match score';
  }

  function isWinner(string $runner, SportGameSnapshot $snapshot): string {
    $stats = $this->esport($snapshot->id());
    if(str_replace("-", ":", $stats->totalScore()) === $runner) return $this->win();
    else return $this->lose();
  }

  function translation(string $market, string $runner): SportMarketTranslation {
    return (new SportMarketTranslation())->runner($runner)->market('sport.market.esport.correctMatchScore');
  }

}
