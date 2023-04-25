<?php namespace App\Sport\Provider;

abstract class SportMarket {

  abstract function name();

  abstract function isOpen(): bool;

  /**
   * @return array<SportMarketRunner>
   */
  abstract function getRunners(): array;

  public function toArray(string $sportType): array {
    $runners = [];

    $isAvailable = false;

    foreach ($this->getRunners() as $runner) {
      $array = $runner->toArray($sportType);
      $runners[] = $array;

      if($array['supported']['status']) $isAvailable = true;
    }

    return [
      'name' => $this->name(),
      'open' => $this->isOpen(),
      'runners' => $runners,
      'isAvailable' => $isAvailable
    ];
  }

}
