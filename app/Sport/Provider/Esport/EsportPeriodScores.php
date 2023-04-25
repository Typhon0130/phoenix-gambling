<?php namespace App\Sport\Provider\Esport;

class EsportPeriodScores {

  private string $periodScores;

  public function __construct(string $periodScores) {
    $this->periodScores = $periodScores;
  }

  public function match(int $number): EsportPeriodScoresMatch {
    if($number < 1) throw new \Exception('Match ID starts from 1, input: ' . $number);
    return new EsportPeriodScoresMatch($this->split()[$number - 1]);
  }

  public function periods(): int {
    return count($this->split());
  }

  public function awayWonAtLeastOne(): bool {
    for($i = 0; $i < $this->periods(); $i++) {
      if($this->match($i + 1)->isAway()) return true;
    }

    return false;
  }

  public function homeWonAtLeastOne(): bool {
    for($i = 0; $i < $this->periods(); $i++) {
      if($this->match($i + 1)->isHome()) return true;
    }

    return false;
  }

  public function drawWonAtLeastOne(): bool {
    for($i = 0; $i < $this->periods(); $i++) {
      if($this->match($i + 1)->isDraw()) return true;
    }

    return false;
  }

  private function split(): array {
    return mb_split(" ", $this->periodScores);
  }

}
