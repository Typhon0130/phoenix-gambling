<?php namespace App\Sport\Provider;

/**
 * Snapshot of the game status at the bet time.
 * @package App\Sport\Provider
 */
abstract class SportGameSnapshot {

  abstract function id(): string;

  abstract function market(): string;

  public function toArray(): array {
    return [
      'id' => $this->id(),
      'market' => $this->market()
    ];
  }

  public static function fromArray(array $array): SportGameSnapshot {
    return new class($array) extends SportGameSnapshot {
      private array $array;

      public function __construct($array) {
        $this->array = $array;
      }

      function id(): string {
        return $this->array['id'];
      }

      public function market(): string {
        return $this->array['market'];
      }
    };
  }

  public static function createSnapshot(SportGame $game, string $market): SportGameSnapshot {
    return new class($game, $market) extends SportGameSnapshot {
      private SportGame $game;
      private string $market;

      public function __construct($game, string $market) {
        $this->game = $game;
        $this->market = $market;
      }

      function id(): string {
        return $this->game->id();
      }

      public function market(): string {
        return $this->market;
      }
    };
  }

}
