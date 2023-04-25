<?php namespace App\Sport\Provider\Esport;

use App\Models\PhoenixGamblingSportHistory;

class ESportData {

  private PhoenixGamblingSportHistory $game;

  public function __construct(PhoenixGamblingSportHistory $game) {
    $this->game = $game;
  }

  /**
   * @deprecated 'scoreboard' parameter ofter disappears. It should be used for client-side info only.
   * @return ESportDataStats
   */
  public function stats(): ESportDataStats {
    return new ESportDataStats($this->game->eSport['scoreboard']);
  }

  public function totalScore(): string {
    return $this->game->eSport['total_score'];
  }

  public function scores(): EsportScores {
    return new EsportScores($this->game->eSport['total_score']);
  }

  public function periodScores(): EsportPeriodScores {
    return new EsportPeriodScores($this->game->eSport['period_scores']);
  }

  public function game(): PhoenixGamblingSportHistory {
    return $this->game;
  }

}
