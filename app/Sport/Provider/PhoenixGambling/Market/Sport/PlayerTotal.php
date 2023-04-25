<?php namespace App\Sport\Provider\PhoenixGambling\Market\Sport;

use App\Sport\Provider\PhoenixGambling\PhoenixGamblingSportMarketHandler;
use App\Sport\Provider\SportGameSnapshot;
use App\Sport\Provider\SportMarketTranslation;

class PlayerTotal extends PhoenixGamblingSportMarketHandler {

  function isHandling(string $market, string $runner): bool {
    return str_ends_with($market, " total") && !str_contains($market, "period") && !str_contains($market, "inning") && !str_contains($market, "half") && !str_contains($market, "set") && (str_contains($runner, "over") || str_contains($runner, "under"));
  }

  function isWinner(string $runner, SportGameSnapshot $snapshot): string {
    $data = $this->getData($snapshot->id())->match();
    $game = $this->findHistoricGame($snapshot->id());
    $team = $this->extractString($snapshot->market(), ' total');

    $betType = $team === $game->home ? 'home' : 'away';

    $int = floatval(str_replace("over ", "", str_replace("under ", "", $runner)));
    $totalScore = $betType === 'home' ? $data->homeScore() : $data->awayScore();

    if(str_starts_with($runner, "over")) {
      if($totalScore >= $int) return $this->win();
    } else if(str_starts_with($runner, "under")) {
      if($totalScore <= $int) return $this->win();
    }

    return $this->lose();
  }

  function translation(string $market, string $runner): SportMarketTranslation {
    return (new SportMarketTranslation())->runner($runner)->market('sport.market.sport.playerTotal', [ 'player' => $this->extractString($market, ' total') ]);
  }

}
