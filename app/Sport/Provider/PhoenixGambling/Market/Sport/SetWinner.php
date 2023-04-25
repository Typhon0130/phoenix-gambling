<?php namespace App\Sport\Provider\PhoenixGambling\Market\Sport;

use App\Sport\Provider\PhoenixGambling\PhoenixGamblingSportMarketHandler;
use App\Sport\Provider\SportGameSnapshot;
use App\Sport\Provider\SportMarketTranslation;

class SetWinner extends PhoenixGamblingSportMarketHandler {

  function isHandling(string $market, string $runner): bool {
    return str_contains($market, "set - winner");
  }

  function isWinner(string $runner, SportGameSnapshot $snapshot): string {
    $setWinner = $this->getData($snapshot->id())->match()->period($this->extract($snapshot->market(), 'set'))->winner();
    $game = $this->findHistoricGame($snapshot->id());

    if($runner === $game->home && $setWinner === 'home') return $this->win();
    if($runner === $game->away && $setWinner === 'away') return $this->win();

    return $this->lose();
  }

  function translation(string $market, string $runner): SportMarketTranslation {
    return (new SportMarketTranslation())->runner($runner)->market('sport.market.sport.setWinner', [ 'set' => $this->extract($market, 'set') ]);
  }

}
