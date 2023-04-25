<?php namespace App\Sport\Provider\PhoenixGambling\Market\Sport;

use App\Sport\Provider\PhoenixGambling\PhoenixGamblingSportMarketHandler;
use App\Sport\Provider\SportGameSnapshot;
use App\Sport\Provider\SportMarketTranslation;

class PeriodBothTeamsToScore extends PhoenixGamblingSportMarketHandler {

  function isHandling(string $market, string $runner): bool {
    return str_contains($market, "period - both teams to score");
  }

  function isWinner(string $runner, SportGameSnapshot $snapshot): string {
    $data = $this->getData($snapshot->id())->match()->period($this->extract($snapshot->market(), 'period'));

    if($data->awayScore() > 0 && $data->homeScore() > 0) return $this->win();
    return $this->lose();
  }

  function translation(string $market, string $runner): SportMarketTranslation {
    return (new SportMarketTranslation())->market('sport.market.sport.periodBothTeamsToScore', [ 'period' => $this->extract($market, 'period') ])->runner($runner);
  }

}
