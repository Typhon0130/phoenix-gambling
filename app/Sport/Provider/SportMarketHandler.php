<?php namespace App\Sport\Provider;

use App\Models\PhoenixGamblingSportHistory;
use App\Sport\Provider\Esport\ESportData;
use App\Sport\Provider\SportRadar\SportRadarData;

abstract class SportMarketHandler {

  abstract function supports(string $sportType): bool;

  abstract function isHandling(string $market, string $runner): bool;

  abstract function isWinner(string $runner, SportGameSnapshot $snapshot): string;

  abstract function translation(string $market, string $runner): SportMarketTranslation;

  public function findHistoricGame(string $id): ?PhoenixGamblingSportHistory {
    return PhoenixGamblingSportHistory::where('srId', $id)->first();
  }

  public function esport(string $game_id): ?ESportData {
    $game = $this->findHistoricGame($game_id);
    return $game ? new ESportData($game) : null;
  }

  public function getData(string $game_id): SportRadarData {
    return new SportRadarData($game_id);
  }

  public function extract(string $subject, string $end): int {
    return intval(preg_replace('/\D/', '', mb_split($end, $subject)[0]));
  }

  public function extractString(string $subject, string $end): string {
    return mb_split($end, $subject)[0];
  }

  protected function win(): string {
    return "win";
  }

  protected function lose(): string {
    return "lose";
  }

  protected function refund(): string {
    return "refund";
  }

}
