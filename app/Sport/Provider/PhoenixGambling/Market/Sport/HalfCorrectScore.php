<?php namespace App\Sport\Provider\PhoenixGambling\Market\Sport;

use App\Sport\Provider\PhoenixGambling\PhoenixGamblingSportMarketHandler;
use App\Sport\Provider\SportGameSnapshot;
use App\Sport\Provider\SportMarketTranslation;

class HalfCorrectScore extends PhoenixGamblingSportMarketHandler {

  function isHandling(string $market, string $runner): bool {
    return str_contains($market, "half - correct score") && $runner !== 'other';
  }

  function isWinner(string $runner, SportGameSnapshot $snapshot): string {
    $data = $this->getData($snapshot->id())->match()->period($this->extract($snapshot->market(), 'half'));

    if($data->totalScore() === $runner) return $this->win();

    return $this->lose();
  }

  function translation(string $market, string $runner): SportMarketTranslation {
    return (new SportMarketTranslation())->market('sport.market.sport.halfCorrectScore', [ 'half' => $this->extract($market, 'half') ])->runner($runner);
  }

}
