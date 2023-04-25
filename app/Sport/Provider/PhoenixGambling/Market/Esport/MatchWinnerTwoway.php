<?php namespace App\Sport\Provider\PhoenixGambling\Market\Esport;

use App\Sport\Provider\PhoenixGambling\PhoenixGamblingEsportMarketHandler;
use App\Sport\Provider\SportGameSnapshot;
use App\Sport\Provider\SportMarketTranslation;
use Illuminate\Support\Facades\Log;

class MatchWinnerTwoway extends PhoenixGamblingEsportMarketHandler {

  function isHandling(string $market, string $runner): bool {
    return $market === 'Match winner - twoway';
  }

  function isWinner(string $runner, SportGameSnapshot $snapshot): string {
    $stats = $this->esport($snapshot->id());

    if($stats->scores()->home() === $stats->scores()->away()) return $this->lose();

    if($runner === $stats->game()->home) {
      if($stats->scores()->home() > $stats->scores()->away()) return $this->win();
      return $this->lose();
    } else if($runner === $stats->game()->away) {
      if($stats->scores()->away() > $stats->scores()->home()) return $this->win();
      return $this->lose();
    } else {
      Log::warning("Failed to determine home/away team from runner (EsportMatchWinnerTwoway) Snapshot ID: " . $snapshot->id());
      return $this->refund();
    }
  }

  function translation(string $market, string $runner): SportMarketTranslation {
    return (new SportMarketTranslation())->runner($runner)->market('sport.market.esport.matchWinnerTwoWay');
  }

}
