<?php namespace App\Sport\Provider\PhoenixGambling\Market\Sport;

use App\Sport\Provider\PhoenixGambling\PhoenixGamblingSportMarketHandler;
use App\Sport\Provider\SportGameSnapshot;
use App\Sport\Provider\SportMarketTranslation;

class OnexTwo extends PhoenixGamblingSportMarketHandler {

  function isHandling(string $market, string $runner): bool {
    return $market === '1x2';
  }

  function isWinner(string $runner, SportGameSnapshot $snapshot): string {
    $match = $this->getData($snapshot->id())->match();
    $game = $this->findHistoricGame($snapshot->id());

    return (($match->winner() === 'draw' && $runner === 'draw')
      || ($match->winner() === 'home' && $runner === $game->home)
      || ($match->winner() === 'away' && $runner === $game->away)) ? $this->win() : $this->lose();
  }

  function translation(string $market, string $runner): SportMarketTranslation {
    return (new SportMarketTranslation())->runner($runner)->market('sport.market.sport.1x2');
  }

}
