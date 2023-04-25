<?php namespace App\Sport\Provider\Esport;

class EsportPeriodScoresMatch {

  private string $periodRaw;

  public function __construct(string $periodRaw) {
    $this->periodRaw = $periodRaw;
  }

  public function status(): string {
    return match ($this->periodRaw) {
      "1-0" => "home",
      "0-0" => "draw",
      "0-1" => "away"
    };
  }

  public function isDraw(): bool {
    return $this->status() === 'draw';
  }

  public function isAway(): bool {
    return $this->status() === 'away';
  }

  public function isHome(): bool {
    return $this->status() === 'home';
  }

}
