<?php namespace App\Currency\Native;

class BitcoinCash extends V17RPCBitcoin {

  function id(): string {
    return "native_bch";
  }

  public function walletId(): string {
    return "bch";
  }

  function name(): string {
    return "BCH";
  }

  function icon(): string {
    return "bch";
  }

  public function alias(): string {
    return 'bitcoin-cash';
  }

  public function displayName(): string {
    return "Bitcoin Cash";
  }

  function style(): string {
    return "#8dc351";
  }

  public function coldWalletBalance(): float {
    try {
      return json_decode(file_get_contents('https://rest.bitcoin.com/v2/address/details/' . $this->option('transfer_address')))->balance;
    } catch (\Exception $e) {
      return 0;
    }
  }

  public function url(): ?string {
    return "https://www.blockchain.com/bch/address/%s";
  }

}
