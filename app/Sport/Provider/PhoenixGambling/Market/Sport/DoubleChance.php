<?php namespace App\Sport\Provider\PhoenixGambling\Market\Sport;

use App\Sport\Provider\PhoenixGambling\PhoenixGamblingSportMarketHandler;
use App\Sport\Provider\SportGameSnapshot;
use App\Sport\Provider\SportMarketTranslation;

class DoubleChance extends PhoenixGamblingSportMarketHandler {

  function isHandling(string $market, string $runner): bool {
    return $market === 'Double chance';
  }

  function isWinner(string $runner, SportGameSnapshot $snapshot): string {
    $data = $this->getData($snapshot->id());
    $game = $this->findHistoricGame($snapshot->id());
    $winner = $data->match()->winner();

    if($runner === $game->home . ' or draw') {
      if($winner === 'home' || $winner === 'draw') return $this->win();
    }

    if($runner === 'draw or ' . $game->away) {
      if($winner === 'draw' || $winner === 'away') return $this->win();
    }

    if($runner === $game->home . ' or ' . $game->away) {
      if($winner === 'home' || $winner === 'away') return $this->win();
    }

    return $this->lose();
  }

  function translation(string $market, string $runner): SportMarketTranslation {
    return (new SportMarketTranslation())->market('sport.market.sport.doubleChance')->runner($runner);
  }

}
