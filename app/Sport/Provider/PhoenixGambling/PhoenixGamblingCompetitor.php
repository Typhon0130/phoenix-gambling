<?php namespace App\Sport\Provider\PhoenixGambling;

use App\Sport\Provider\SportCompetitor;

class PhoenixGamblingCompetitor extends SportCompetitor {

  private string $name;

  public function __construct(string $name) {
    $this->name = $name;
  }

  function name(): string {
    return $this->name;
  }

  function logo(): ?string {
    return null;
  }

}
