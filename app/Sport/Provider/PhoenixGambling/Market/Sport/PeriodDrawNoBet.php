<?php namespace App\Sport\Provider\PhoenixGambling\Market\Sport;

use App\Sport\Provider\PhoenixGambling\PhoenixGamblingSportMarketHandler;
use App\Sport\Provider\SportGameSnapshot;
use App\Sport\Provider\SportMarketTranslation;

class PeriodDrawNoBet extends PhoenixGamblingSportMarketHandler {

  function isHandling(string $market, string $runner): bool {
    return str_contains($market, "period - draw no bet");
  }

  function isWinner(string $runner, SportGameSnapshot $snapshot): string {
    $winner = $this->getData($snapshot->id())->match()->period($this->extract($snapshot->market(), 'period'))->winner();
    $game = $this->findHistoricGame($snapshot->id());

    if($winner === 'draw') return $this->refund();
    if($winner === 'home' && $runner === $game->home) return $this->win();
    if($winner === 'away' && $runner === $game->away) return $this->win();

    return $this->lose();
  }

  function translation(string $market, string $runner): SportMarketTranslation {
    return (new SportMarketTranslation())->market('sport.market.sport.periodDrawNoBet', [ 'period' => $this->extract($market, 'period') ])->runner($runner);
  }

}
