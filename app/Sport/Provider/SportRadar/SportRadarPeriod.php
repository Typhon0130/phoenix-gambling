<?php namespace App\Sport\Provider\SportRadar;

class SportRadarPeriod {

  private int $home;
  private int $away;

  public function __construct($home, $away) {
    $this->home = $home;
    $this->away = $away;
  }

  public function homeScore(): int {
    return $this->home;
  }

  public function awayScore(): int {
    return $this->away;
  }

  public function winner(): string {
    return $this->homeScore() === $this->awayScore() ? 'draw' : ($this->homeScore() > $this->awayScore() ? 'home' : 'away');
  }

  public function totalScore(): string {
    return $this->homeScore() . ':' . $this->awayScore();
  }

}
