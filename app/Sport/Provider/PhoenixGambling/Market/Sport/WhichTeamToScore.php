<?php namespace App\Sport\Provider\PhoenixGambling\Market\Sport;

use App\Sport\Provider\PhoenixGambling\PhoenixGamblingSportMarketHandler;
use App\Sport\Provider\SportGameSnapshot;
use App\Sport\Provider\SportMarketTranslation;

class WhichTeamToScore extends PhoenixGamblingSportMarketHandler {

  function isHandling(string $market, string $runner): bool {
    return $market === 'Which team to score';
  }

  function isWinner(string $runner, SportGameSnapshot $snapshot): string {
    $data = $this->getData($snapshot->id())->match();
    $game = $this->findHistoricGame($snapshot->id());

    if($runner === 'both teams' && $data->awayScore() > 0 && $data->homeScore() > 0) return $this->win();
    if($runner === 'only ' . $game->home && $data->awayScore() === 0 && $data->homeScore() > 0) return $this->win();
    if($runner === 'only ' . $game->away && $data->homeScore() === 0 && $data->awayScore() > 0) return $this->win();

    return $this->lose();
  }

  function translation(string $market, string $runner): SportMarketTranslation {
    return (new SportMarketTranslation())->runner($runner)->market('sport.market.sport.whichTeamToScore');
  }

}
