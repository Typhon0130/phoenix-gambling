<?php namespace App\Sport\Provider\PhoenixGambling;

use App\Sport\Provider\SportMarketRunner;

class PhoenixGamblingRunner extends SportMarketRunner {

  private string $marketName;
  private array $runner;

  public function __construct(string $marketName, array $runner) {
    $this->marketName = $marketName;
    $this->runner = $runner;
  }

  function marketName(): string {
    return $this->marketName;
  }

  function name(): string {
    return $this->runner['name'];
  }

  function isOpen(): bool {
    return $this->runner['isEnabled'] && $this->price() > 0;
  }

  function price(): float {
    return $this->runner['odds'];
  }

}
