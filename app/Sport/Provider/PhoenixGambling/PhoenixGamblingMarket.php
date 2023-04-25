<?php namespace App\Sport\Provider\PhoenixGambling;

use App\Sport\Provider\SportMarket;

class PhoenixGamblingMarket extends SportMarket {

  private array $market;

  public function __construct(array $market) {
    $this->market = $market;
  }

  function name() {
    return $this->market['name'];
  }

  function isOpen(): bool {
    return $this->market['isEnabled'];
  }

  function getRunners(): array {
    $runners = [];

    foreach ($this->market['runners'] as $runner)
      $runners[] = new PhoenixGamblingRunner($this->name(), $runner);

    return $runners;
  }

}
