<?php namespace App\Currency\Commerce;

class CommerceDAI extends CommerceCurrency {

  function id(): string {
    return "commerce_eth_dai";
  }

  function walletId(): string {
    return "dai";
  }

  function name(): string {
    return "DAI";
  }

  function alias(): string {
    return "dai";
  }

  function displayName(): string {
    return "DAI";
  }

  function icon(): string {
    return "dai";
  }

  function style(): string {
    return '#f4ad27';
  }

  public function coinbaseId(): string {
    return 'DAI';
  }

  public function coinbaseName(): string {
    return 'dai';
  }

}
