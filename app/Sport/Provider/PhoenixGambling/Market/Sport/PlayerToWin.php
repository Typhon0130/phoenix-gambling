<?php namespace App\Sport\Provider\PhoenixGambling\Market\Sport;

use App\Sport\Provider\PhoenixGambling\PhoenixGamblingSportMarketHandler;
use App\Sport\Provider\SportGameSnapshot;
use App\Sport\Provider\SportMarketTranslation;

class PlayerToWin extends PhoenixGamblingSportMarketHandler {

  function isHandling(string $market, string $runner): bool {
    return str_ends_with($market, " to win") && !str_contains($market, "inning") && !str_contains($market, "period") && !str_contains($market, "half") && !str_contains($market, "set") && ($runner === 'yes' || $runner === 'no');
  }

  function isWinner(string $runner, SportGameSnapshot $snapshot): string {
    $data = $this->getData($snapshot->id())->match();
    $game = $this->findHistoricGame($snapshot->id());
    $team = $this->extractString($snapshot->market(), ' to win');

    $betType = $team === $game->home ? 'home' : 'away';

    if($runner === 'yes' && (($betType === 'home' && $data->winner() === 'home') || ($betType === 'away' && $data->winner() === 'away'))) return $this->win();
    if($runner === 'no' && (($betType === 'home' && $data->winner() !== 'home') || ($betType === 'away' && $data->winner() !== 'away'))) return $this->win();

    return $this->lose();
  }

  function translation(string $market, string $runner): SportMarketTranslation {
    return (new SportMarketTranslation())->runner($runner === 'yes' ? 'sport.market.yes' : 'sport.market.no')->market('sport.market.sport.playerToWin', [ 'player' => $this->extractString($market, ' to win') ]);
  }

}
