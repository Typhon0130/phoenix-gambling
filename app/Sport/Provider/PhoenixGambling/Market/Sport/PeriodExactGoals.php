<?php namespace App\Sport\Provider\PhoenixGambling\Market\Sport;

use App\Sport\Provider\PhoenixGambling\PhoenixGamblingSportMarketHandler;
use App\Sport\Provider\SportGameSnapshot;
use App\Sport\Provider\SportMarketTranslation;

class PeriodExactGoals extends PhoenixGamblingSportMarketHandler {

  function isHandling(string $market, string $runner): bool {
    return str_contains($market, "period - exact goals") && $runner !== 'other';
  }

  function isWinner(string $runner, SportGameSnapshot $snapshot): string {
    $period = $this->getData($snapshot->id())->match()->period($this->extract($snapshot->market(), 'period'));

    if($runner === $period->totalScore()) return $this->win();

    return $this->lose();
  }

  function translation(string $market, string $runner): SportMarketTranslation {
    return (new SportMarketTranslation())->market('sport.market.sport.periodExactGoals', [ 'period' => $this->extract($market, 'period') ])->runner($runner);
  }

}
