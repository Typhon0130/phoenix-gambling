<?php namespace App\Currency\Native;

class Dogecoin extends V16RPCBitcoin {

  function id(): string {
    return "native_doge";
  }

  public function walletId(): string {
    return "doge";
  }

  function name(): string {
    return "DOGE";
  }

  public function alias(): string {
    return "dogecoin";
  }

  public function displayName(): string {
    return "Dogecoin";
  }

  function icon(): string {
    return "dogecoin";
  }

  public function style(): string {
    return "#c2a633";
  }

  public function url(): ?string {
    return "https://live.blockcypher.com/doge/address/%s/";
  }

}
