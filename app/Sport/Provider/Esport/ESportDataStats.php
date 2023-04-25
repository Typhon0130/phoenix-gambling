<?php namespace App\Sport\Provider\ESport;

class ESportDataStats {

  private array $data;

  public function __construct(array $data) {
    $this->data = $data;
  }

  public function currentCTTeam(): string {
    return $this->data['current_ct_team'];
  }

  public function homeWonRounds(): int {
    return intval($this->data['home_won_rounds']);
  }

  public function awayWonRounds(): int {
    return intval($this->data['away_won_rounds']);
  }

  public function currentRound(): string {
    return $this->data['current_round'];
  }

  public function homeKills(): int {
    return intval($this->data['home_kills']);
  }

  public function awayKills(): int {
    return intval($this->data['away_kills']);
  }

  public function homeDestroyedTurrets(): string {
    return $this->data['home_destroyed_turrets'];
  }

  public function awayDestroyedTurrets(): string {
    return $this->data['away_destroyed_turrets'];
  }

  public function homeGold(): string {
    return $this->data['home_gold'];
  }

  public function awayGold(): string {
    return $this->data['away_gold'];
  }

  public function homeDestroyedTowers(): string {
    return $this->data['home_destroyed_towers'];
  }

  public function awayDestroyedTowers(): string {
    return $this->data['away_destroyed_towers'];
  }

  public function homeGoals(): string {
    return $this->data['home_goals'];
  }

  public function awayGoals(): string {
    return $this->data['away_goals'];
  }

  public function time(): string {
    return $this->data['time'];
  }

  public function gameTime(): string {
    return $this->data['game_time'];
  }

}
