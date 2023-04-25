<?php namespace App\Sport\Provider\PhoenixGambling\Market\Sport;

use App\Sport\Provider\PhoenixGambling\PhoenixGamblingSportMarketHandler;
use App\Sport\Provider\SportGameSnapshot;
use App\Sport\Provider\SportMarketTranslation;

class PlayerExactGoals extends PhoenixGamblingSportMarketHandler {

  function isHandling(string $market, string $runner): bool {
    return str_ends_with($market, " exact goals") && !str_contains($market, "inning") && !str_contains($market, "period") && !str_contains($market, "half") && !str_contains($market, "set") && $runner !== 'other';
  }

  function isWinner(string $runner, SportGameSnapshot $snapshot): string {
    $data = $this->getData($snapshot->id())->match();
    $game = $this->findHistoricGame($snapshot->id());
    $team = $this->extractString($snapshot->market(), ' exact goals');

    $betType = $team === $game->home ? 'home' : 'away';
    $score = $betType === 'home' ? $data->homeScore() : $data->awayScore();

    if(str_ends_with($runner, "+")) {
      if($score > intval(str_replace("+", "", $runner))) return $this->win();
    } else {
      if(intval($runner) === $score) return $this->win();
    }

    return $this->lose();
  }

  function translation(string $market, string $runner): SportMarketTranslation {
    return (new SportMarketTranslation())->runner($runner)->market('sport.market.sport.playerExactGoals', [ 'player' => $this->extractString($market, ' exact goals') ]);
  }

}
