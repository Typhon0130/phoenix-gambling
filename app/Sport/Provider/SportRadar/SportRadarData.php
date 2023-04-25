<?php namespace App\Sport\Provider\SportRadar;

class SportRadarData {

  private array $data;

  public function __construct(string $id) {
    $id = str_replace('sr:match:', "", $id);

    $this->data = json_decode(file_get_contents("https://lmt.fn.sportradar.com/common/en/Etc:UTC/gismo/match_timelinedelta/" . $id), true)['doc'][0]['data'];
  }

  public function match(): SportRadarMatch {
    return new SportRadarMatch($this->data['match']);
  }

  public function events(): SportRadarEvents {
    return new SportRadarEvents($this->data['events']);
  }

}
