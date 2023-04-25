<?php namespace App\Currency\Commerce;

class CommerceApe extends CommerceCurrency {

  function id(): string {
    return 'commerce_ape';
  }

  function walletId(): string {
    return 'ape';
  }

  function name(): string {
    return 'APE';
  }

  function alias(): string {
    return 'apecoin';
  }

  function displayName(): string {
    return 'APE';
  }

  function icon(): string {
    return 'apecoin';
  }

  function style(): string {
    return 'white';
  }

  function coinbaseId(): string {
    return 'APE';
  }

  public function coinbaseName(): string {
    return "apecoin";
  }

}
