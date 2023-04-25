<?php namespace App\Sport\Provider\PhoenixGambling\Market\Sport;

use App\Sport\Provider\PhoenixGambling\PhoenixGamblingSportMarketHandler;
use App\Sport\Provider\SportGameSnapshot;
use App\Sport\Provider\SportMarketTranslation;

class HalfOddEven extends PhoenixGamblingSportMarketHandler {

  function isHandling(string $market, string $runner): bool {
    return str_contains($market, "half - odd/even") && ($runner === 'odd' || $runner === 'even');
  }

  function isWinner(string $runner, SportGameSnapshot $snapshot): string {
    $data = $this->getData($snapshot->id())->match()->period($this->extract($snapshot->market(), 'half'));
    $total = $data->awayScore() + $data->homeScore();
    $isEven = $total % 2 === 0;

    if($runner === 'odd' && !$isEven) return $this->win();
    if($runner === 'even' && $isEven) return $this->win();

    return $this->lose();
  }

  function translation(string $market, string $runner): SportMarketTranslation {
    return (new SportMarketTranslation())->market('sport.market.sport.halfOddEven', [ 'half' => $this->extract($market, 'half') ])->runner($runner === 'odd' ? 'sport.market.odd' : 'sport.market.even');
  }

}
