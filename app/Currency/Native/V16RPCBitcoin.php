<?php namespace App\Currency\Native;

use Nbobtc\Command\Command;

abstract class V16RPCBitcoin extends BitcoinCurrency {

  private function balance($account): float {
    try {
      $client = $this->getClient();
      $command = new Command('getbalance', $account);
      $response = $client->sendCommand($command);
      $contents = json_decode($response->getBody()->getContents(), true);
      return $contents['result'];
    } catch (\Exception $e) {
      return 0;
    }
  }

  public function coldWalletBalance(): float {
    return $this->balance('deposit');
  }

  public function isRunning(): bool {
    return $this->coldWalletBalance() != -1;
  }

  public function send(string $from, string $to, float $sum) {
    $client = $this->getClient();

    $fee = 0; // TODO
    $client->sendCommand(new Command('settxfee', [number_format($fee, 8, '.', '')]));

    $account = json_decode($client->sendCommand(new Command('getaccount', $from))->getBody()->getContents())->result;
    $client->sendCommand(new Command('sendfrom', [$account, $to, number_format($sum - $fee, 8, '.', '')]));
  }

}
