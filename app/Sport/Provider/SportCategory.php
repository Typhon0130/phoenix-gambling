<?php namespace App\Sport\Provider;

abstract class SportCategory {

  abstract function id(): string;

  abstract function name(): string;

  abstract function icon(): string;

  abstract function liveCount(): int;

  abstract function totalCount(): int;

  abstract function sportType(): string;

  /**
   * @return array<SportGame>
   */
  abstract function getGames(bool $isLive): array;

  public function toArray(bool $includeGames = true, bool $oneMarket = false, bool $isLive = true): array {
    $games = [];

    if($includeGames) foreach ($this->getGames($isLive) as $game) {
      $games[] = $game->toArray($oneMarket);
    }

    return [
      'id' => $this->id(),
      'name' => $this->name(),
      'icon' => $this->icon(),
      'liveCount' => $this->liveCount(),
      'totalCount' => $this->totalCount(),
      'games' => $games,
      'sportType' => $this->sportType()
    ];
  }

}
