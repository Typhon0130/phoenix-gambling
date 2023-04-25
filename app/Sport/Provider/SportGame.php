<?php namespace App\Sport\Provider;

abstract class SportGame {

  abstract function sportType(): string;

  abstract function id(): string;

  abstract function category(): SportCategory;

  abstract function name(): string;

  abstract function isLive(): bool;

  abstract function isOpen(): bool;

  /**
   * @return array<SportCompetitor>
   */
  abstract function competitors(): array;

  /**
   * @return array<SportMarket>
   */
  abstract function markets(): array;

  abstract function league(): ?SportLeague;

  abstract function liveStatus(): ?SportLiveStatus;

  abstract function sportRadarId(): int;

  abstract function twitch(): ?string;

  public function toArray($oneMarket = false): array {
    $competitors = [];
    $markets = [];

    foreach ($this->competitors() as $competitor)
      $competitors[] = $competitor->toArray();

    $totalOdds = 0;

    foreach ($this->markets() as $market) {
      $array =  $market->toArray($this->sportType());
      $markets[] = $array;
      if($array['isAvailable']) $totalOdds++;
    }

    return [
      'sportType' => $this->sportType(),
      'id' => $this->id(),
      'srId' => $this->sportRadarId(),
      'category' => [
        'id' => $this->category()->id(),
        'name' => $this->category()->name()
      ],
      'live' => $this->isLive(),
      'name' => $this->name(),
      'open' => $this->isOpen(),
      'competitors' => $competitors,
      'markets' => $oneMarket ? array_slice($markets, 0, 1) : $markets,
      'league' => $this->league() ? $this->league()->toArray() : null,
      'liveStatus' => $this->liveStatus()->toArray(),
      'totalOddsMT' => $totalOdds,
      'twitch' => $this->twitch()
    ];
  }

}
