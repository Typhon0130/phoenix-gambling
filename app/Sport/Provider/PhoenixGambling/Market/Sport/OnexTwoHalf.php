<?php namespace App\Sport\Provider\PhoenixGambling\Market\Sport;

use App\Sport\Provider\PhoenixGambling\PhoenixGamblingSportMarketHandler;
use App\Sport\Provider\SportGameSnapshot;
use App\Sport\Provider\SportMarketTranslation;

class OnexTwoHalf extends PhoenixGamblingSportMarketHandler {

  function isHandling(string $market, string $runner): bool {
    return str_contains($market, "half - 1x2");
  }

  function isWinner(string $runner, SportGameSnapshot $snapshot): string {
    $period = $this->getData($snapshot->id())->match()->period($this->extract($snapshot->market(), 'half'));
    $game = $this->findHistoricGame($snapshot->id());

    return (($period->winner() === 'draw' && $runner === 'draw')
      || ($period->winner() === 'home' && $runner === $game->home)
      || ($period->winner() === 'away' && $runner === $game->away)) ? $this->win() : $this->lose();
  }

  function translation(string $market, string $runner): SportMarketTranslation {
    return (new SportMarketTranslation())->market('sport.market.sport.1x2Half', [ 'half' => $this->extract($market, 'half') ])->runner($runner);
  }

}
