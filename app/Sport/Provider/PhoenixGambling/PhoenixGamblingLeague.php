<?php namespace App\Sport\Provider\PhoenixGambling;

use App\Sport\Provider\SportLeague;

class PhoenixGamblingLeague extends SportLeague {

  private string $categoryId, $categoryName, $tournamentId, $tournamentName;

  public function __construct($categoryId, $categoryName, $tournamentId, $tournamentName) {
    $this->categoryId = $categoryId;
    $this->categoryName = $categoryName;
    $this->tournamentId = $tournamentId;
    $this->tournamentName = $tournamentName;
  }

  function id(): ?string {
    return $this->categoryId . ":" . $this->tournamentId;
  }

  function name(): string {
    return $this->categoryName . ' - ' . $this->tournamentName;
  }

}
