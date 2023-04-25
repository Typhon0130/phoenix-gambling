<?php namespace App\Sport\Provider\PhoenixGambling;

use App\Models\PhoenixGamblingSportData;
use App\Sport\Provider\SportCategory;

class PhoenixGamblingSportCategory extends SportCategory {

  private string $id, $name, $sportType;
  private int $liveCount, $totalCount;

  public function __construct(string $id, string $name, int $liveCount, int $totalCount, string $sportType) {
    $this->id = $id;
    $this->name = $name;
    $this->liveCount = $liveCount;
    $this->totalCount = $totalCount;
    $this->sportType = $sportType;
  }

  function id(): string {
    return $this->id;
  }

  function name(): string {
    return $this->name;
  }

  public function liveCount(): int {
    if(PhoenixGamblingLine::$mode === 'ws') {
      $count = 0;

      foreach ($this->getGames(true) as $game) {
        if($game->isLive()) $count++;
      }

      return $count;
    }
    return $this->liveCount;
  }

  public function totalCount(): int {
    /*if(PhoenixGamblingLine::$mode === 'ws')
      return count($this->getGames());*/
    return $this->totalCount;
  }

  public function sportType(): string {
    return $this->sportType;
  }

  function icon(): string {
    return match ($this->id()) {
      "soccer", "american-football", "futsal", "aussie-rules", "floorball", "rush-football" => "fad fa-futbol",
      "ice-hockey", "bandy" => "fad fa-hockey-puck",
      "tennis" => "fad fa-tennis-ball",
      "table-tennis" => "fad fa-table-tennis",
      "volleyball" => "fad fa-volleyball-ball",
      "boxing" => "fad fa-boxing-glove",
      "basketball" => "fad fa-basketball-ball",
      "baseball", "handball" => "fad fa-baseball-ball",
      "badminton" => "fad fa-shuttlecock",
      "cricket" => "fad fa-cricket",
      "valorant" => "valorant",
      "cs-go" => "csgo",
      "mobile-legends" => "mobilelegends",
      "dota-2" => "dota",
      "arena-of-valor" => "arenaofvalor",
      "call-of-duty" => "cod",
      "league-of-legends" => "lol",
      default => $this->sportType() === 'SPORTS' ? 'sport' : 'esport'
    };
  }

  function getGames(bool $isLive): array {
    $games = [];

    $query = PhoenixGamblingSportData::where('sportId', $this->id)->where('status', '!=', 'DISABLED');
    if($isLive) $query = $query->where('status', 'LIVE');

    $json = $query->get()->toArray();

    foreach ($json as $game) {
      try {
        if(!is_array($game)) continue;

        $g = new PhoenixGamblingGame($this, $game);

        $ok = false;
        foreach($g->markets() as $market) {
          if($market->isOpen() && count($market->getRunners()) > 0) {
            $ok = true;
            break;
          }
        }

        if($ok) $games[] = $g;
      } catch (\Exception) {}
    }

    return $games;
  }

}
