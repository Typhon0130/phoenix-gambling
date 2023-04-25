<?php namespace App\Currency\Chain;

abstract class EthereumChain extends CurrencyChain {

  public function id(): string {
    return "ethereum";
  }

  public function name(): string {
    return "Ethereum (ERC20)";
  }

}
