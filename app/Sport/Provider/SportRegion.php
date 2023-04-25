<?php namespace App\Sport\Provider;

abstract class SportRegion {

  abstract function name(): string;

  abstract function getLeagues(): array;

  public function toArray(): array {
    $leagues = [];

    foreach ($this->getLeagues() as $league)
      $leagues[] = $league->toArray();

    return [
      'name' => $this->name(),
      'leagues' => $leagues
    ];
  }

}
