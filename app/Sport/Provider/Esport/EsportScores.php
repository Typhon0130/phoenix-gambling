<?php namespace App\Sport\Provider\Esport;

class EsportScores {

  private string $totalScores;

  public function __construct(string $totalScores) {
    $this->totalScores = $totalScores;
  }

  private function split(): array {
    return mb_split("-", $this->totalScores);
  }

  public function home(): int {
    return intval($this->split()[0]);
  }

  public function away(): int {
    return intval($this->split()[1]);
  }

}
