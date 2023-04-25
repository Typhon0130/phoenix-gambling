<?php namespace App\Currency\Chain;

abstract class PolygonChain extends CurrencyChain {

  public function id(): string {
    return "polygon";
  }

  public function name(): string {
    return "Polygon";
  }

}
