<?php namespace App\Currency\Native;

use Nbobtc\Command\Command;

abstract class V17RPCBitcoin extends BitcoinCurrency {

  public function send(string $from, string $to, float $sum) {
    $this->getClient()->sendCommand(new Command('sendtoaddress', [$to, $sum, "", "", true]));
  }

  public function isRunning(): bool {
    try {
      $this->getClient()->sendCommand(new Command('getbalance'));
      return true;
    } catch (\Exception $e) {
      return false;
    }
  }

}
