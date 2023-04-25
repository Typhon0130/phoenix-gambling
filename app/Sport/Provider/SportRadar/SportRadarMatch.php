<?php namespace App\Sport\Provider\SportRadar;

class SportRadarMatch {

  private array $array;

  public function __construct($array) {
    $this->array = $array;
  }

  public function isFinished(): bool {
    if (isset($this->array['status']) && $this->array['status']['name'] === 'Ended') return true;
    return false;
  }

  public function homeScore(): int {
    return $this->array['result']['home'];
  }

  public function awayScore(): int {
    return $this->array['result']['away'];
  }

  public function score(): string {
    return $this->homeScore() . ':' . $this->awayScore();
  }

  public function winner(): string {
    return $this->homeScore() === $this->awayScore() ? 'draw' : ($this->array['result']['winner'] === 'home' ? 'home'
      : ($this->array['result']['winner'] === 'away' ? 'away' : ($this->homeScore() > $this->awayScore() ? 'home' : 'away')));
  }

  public function period(int $period): ?SportRadarPeriod {
    if (!isset($this->array['periods']['p' . $period])) return null;
    return new SportRadarPeriod($this->array['periods']['p' . $period]['home'], $this->array['periods']['p' . $period]['away']);
  }

  public function periods(): int {
    return count($this->array['periods']);
  }

}
