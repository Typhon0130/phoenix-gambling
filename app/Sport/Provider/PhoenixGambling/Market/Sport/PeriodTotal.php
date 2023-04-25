<?php namespace App\Sport\Provider\PhoenixGambling\Market\Sport;

use App\Sport\Provider\PhoenixGambling\PhoenixGamblingSportMarketHandler;
use App\Sport\Provider\SportGameSnapshot;
use App\Sport\Provider\SportMarketTranslation;

class PeriodTotal extends PhoenixGamblingSportMarketHandler {

  function isHandling(string $market, string $runner): bool {
    return str_contains($market, "period - total") && (str_contains($runner, "over") || str_contains($runner, "under"));
  }

  function isWinner(string $runner, SportGameSnapshot $snapshot): string {
    $int = floatval(str_replace("over ", "", str_replace("under ", "", $runner)));
    $data = $this->getData($snapshot->id())->match()->period($this->extract($snapshot->market(), 'period'));
    $totalScore = $data->homeScore() + $data->awayScore();

    if(str_starts_with($runner, "over")) {
      if($totalScore >= $int) return $this->win();
    } else if(str_starts_with($runner, "under")) {
      if($totalScore <= $int) return $this->win();
    }

    return $this->lose();
  }

  function translation(string $market, string $runner): SportMarketTranslation {
    return (new SportMarketTranslation())->market('sport.market.sport.periodTotal', [ 'period' => $this->extract($market, 'period') ])->runner($runner);
  }

}
