<?php namespace App\Sport\Provider;

abstract class SportLineProvider {

  /**
   * @return array<SportCategory>
   */
  abstract function getCategories(): array;

  abstract function findGame(string $id): ?SportGame;

  abstract function findMarket(string $sportType, string $market, string $runner): ?SportMarketHandler;

  public function findCategory(string $id): ?SportCategory {
    foreach ($this->getCategories() as $category)
      if ($category->id() === $id) return $category;

    return null;
  }

}
