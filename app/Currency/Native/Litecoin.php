<?php namespace App\Currency\Native;

class Litecoin extends V16RPCBitcoin {

  function id(): string {
    return "native_ltc";
  }

  public function walletId(): string {
    return "ltc";
  }

  function name(): string {
    return "LTC";
  }

  public function alias(): string {
    return 'litecoin';
  }

  public function displayName(): string {
    return "Litecoin";
  }

  function icon(): string {
    return "ltc";
  }

  public function style(): string {
    return "#bfbbbb";
  }

  public function url(): ?string {
    return "https://live.blockcypher.com/ltc/address/%s/";
  }

}
