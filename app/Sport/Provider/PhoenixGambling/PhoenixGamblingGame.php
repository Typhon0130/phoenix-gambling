<?php namespace App\Sport\Provider\PhoenixGambling;

use App\Sport\Provider\SportCategory;
use App\Sport\Provider\SportGame;
use App\Sport\Provider\SportLeague;
use App\Sport\Provider\SportLiveStatus;

class PhoenixGamblingGame extends SportGame {

  private array $data;
  private SportCategory $category;

  public function __construct(SportCategory $category, array $data) {
    $this->category = $category;
    $this->data = $data;
  }

  public function sportType(): string {
    return $this->data['sportType'];
  }

  function id(): string {
    return $this->data['srId'];
  }

  function category(): SportCategory {
    return $this->category;
  }

  function name(): string {
    return $this->data['home'] . ' - ' . $this->data['away'];
  }

  function isLive(): bool {
    return $this->data['status'] === 'LIVE';
  }

  function isOpen(): bool {
    return $this->sportType() === 'SPORTS' ? $this->data['status'] !== 'DISABLED' : $this->data['matchStatus'] !== 'Closed';
  }

  function competitors(): array {
    return [
      new PhoenixGamblingCompetitor($this->data['home']),
      new PhoenixGamblingCompetitor($this->data['away'])
    ];
  }

  function markets(): array {
    $markets = [];

    foreach ($this->data['markets'] as $market)
      $markets[] = new PhoenixGamblingMarket($market);

    return $markets;
  }

  function league(): ?SportLeague {
    return new PhoenixGamblingLeague($this->data['categoryId'], $this->data['categoryName'],
      $this->data['tournamentId'], $this->data['tournamentName']);
  }

  function liveStatus(): ?SportLiveStatus {
    return new PhoenixGamblingLiveStatus($this->data['matchStatus'], $this->data['time'] ?? '-', $this->data['score'],
      $this->data['scheduledTime']);
  }

  public function twitch(): ?string {
    return $this->data['twitch'];
  }

  function sportRadarId(): int {
    $s = str_replace("sr:match:", "", $this->id());
    return str_replace("od:match:", "", $s);
  }

  public function toArray($oneMarket = false): array {
    $result = parent::toArray($oneMarket);
    if($this->sportType() === 'ESPORTS') $result['eSport'] = $this->data['eSport'];
    return $result;
  }

}
