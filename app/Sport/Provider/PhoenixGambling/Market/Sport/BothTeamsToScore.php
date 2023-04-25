<?php namespace App\Sport\Provider\PhoenixGambling\Market\Sport;

use App\Sport\Provider\PhoenixGambling\PhoenixGamblingSportMarketHandler;
use App\Sport\Provider\SportGameSnapshot;
use App\Sport\Provider\SportMarketTranslation;

class BothTeamsToScore extends PhoenixGamblingSportMarketHandler {

  function isHandling(string $market, string $runner): bool {
    return $market === 'Both teams to score';
  }

  function isWinner(string $runner, SportGameSnapshot $snapshot): string {
    $data = $this->getData($snapshot->id());

    if($data->match()->awayScore() > 0 && $data->match()->homeScore() > 0) return $this->win();
    return $this->lose();
  }

  function translation(string $market, string $runner): SportMarketTranslation {
    return (new SportMarketTranslation())->runner($runner === 'yes' ? 'sport.market.yes' : 'sport.market.no')->market('sport.market.sport.bothTeamsToScore');
  }

}
