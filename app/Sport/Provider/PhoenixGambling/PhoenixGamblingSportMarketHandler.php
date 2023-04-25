<?php namespace App\Sport\Provider\PhoenixGambling;

use App\Sport\Provider\SportMarketHandler;

abstract class PhoenixGamblingSportMarketHandler extends SportMarketHandler {

  public function supports(string $sportType): bool {
    return $sportType === 'SPORTS';
  }

}
