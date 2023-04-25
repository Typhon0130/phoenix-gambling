<?php namespace App\Sport\Provider;

use App\Sport\Provider\PhoenixGambling\Market\NoOpMarketHandler;
use App\Sport\Sport;

abstract class SportMarketRunner {

  abstract function marketName(): string;

  abstract function name(): string;

  abstract function isOpen(): bool;

  abstract function price(): float;

  public function toArray(string $sportType) {
    $handler = Sport::getLine()->findMarket($sportType, $this->marketName(), $this->name());
    if($handler == null) $handler = new NoOpMarketHandler();

    return [
      'name' => $this->name(),
      'open' => $this->isOpen(),
      'price' => $this->price(),
      'translation' => $handler?->translation($this->marketName(), $this->name())->toArray(),
      'supported' => [
        'status' => !($handler instanceof NoOpMarketHandler),
        'class' => str_replace("App\\Sport\\Provider\\PhoenixGambling\\Market\\", "", get_class($handler))
      ]
    ];
  }

}
