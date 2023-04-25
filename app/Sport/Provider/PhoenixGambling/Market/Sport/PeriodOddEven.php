<?php namespace App\Sport\Provider\PhoenixGambling\Market\Sport;

use App\Sport\Provider\PhoenixGambling\PhoenixGamblingSportMarketHandler;
use App\Sport\Provider\SportGameSnapshot;
use App\Sport\Provider\SportMarketTranslation;

class PeriodOddEven extends PhoenixGamblingSportMarketHandler {

  function isHandling(string $market, string $runner): bool {
    return str_contains($market, "period - odd/even") && ($runner === 'odd' || $runner === 'even');
  }

  function isWinner(string $runner, SportGameSnapshot $snapshot): string {
    $data = $this->getData($snapshot->id())->match()->period($this->extract($snapshot->market(), 'period'));
    $total = $data->awayScore() + $data->homeScore();
    $isEven = $total % 2 === 0;

    if($runner === 'odd' && !$isEven) return $this->win();
    if($runner === 'even' && $isEven) return $this->win();

    return $this->lose();
  }

  function translation(string $market, string $runner): SportMarketTranslation {
    return (new SportMarketTranslation())->market('sport.market.sport.periodOddEven', [ 'period' => $this->extract($market, 'period') ])->runner($runner === 'odd' ? 'sport.market.odd' : 'sport.market.even');
  }

}
