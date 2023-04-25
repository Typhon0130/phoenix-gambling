<?php namespace App\Sport\Provider;

class SportMarketTranslation {

  private string $marketKey;
  private string $runnerKey;
  private array $marketData = [];
  private array $runnerData = [];

  public function market(string $key, array $data = []): SportMarketTranslation {
    $this->marketKey = $key;
    $this->marketData = $data;
    return $this;
  }

  public function runner(string $key, array $data = []): SportMarketTranslation {
    $this->runnerKey = $key;
    $this->runnerData = $data;
    return $this;
  }

  public function same(string $market, $runner): SportMarketTranslation {
    $this->marketKey = $market;
    $this->runnerKey = $runner;
    return $this;
  }

  public function toArray(): array {
    return [
      'market' => [
        'key' => $this->marketKey,
        'data' => $this->marketData
      ],
      'runner' => [
        'key' => $this->runnerKey,
        'data' => $this->runnerData
      ]
    ];
  }

}
