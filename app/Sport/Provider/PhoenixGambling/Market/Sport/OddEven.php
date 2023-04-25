<?php namespace App\Sport\Provider\PhoenixGambling\Market\Sport;

use App\Sport\Provider\PhoenixGambling\PhoenixGamblingSportMarketHandler;
use App\Sport\Provider\SportGameSnapshot;
use App\Sport\Provider\SportMarketTranslation;

class OddEven extends PhoenixGamblingSportMarketHandler {

  function isHandling(string $market, string $runner): bool {
    return $market === 'Odd/even';
  }

  function isWinner(string $runner, SportGameSnapshot $snapshot): string {
    $data = $this->getData($snapshot->id())->match();
    $total = $data->awayScore() + $data->homeScore();
    $isEven = $total % 2 === 0;

    if($runner === 'odd' && !$isEven) return $this->win();
    if($runner === 'even' && $isEven) return $this->win();

    return $this->lose();
  }

  function translation(string $market, string $runner): SportMarketTranslation {
    return (new SportMarketTranslation())->runner($runner === 'odd' ? 'sport.market.odd' : 'sport.market.even')->market('sport.market.sport.oddEven');
  }

}
