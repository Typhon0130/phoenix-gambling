<?php namespace App\Utils;

class AnalyticsReport {

  private array $report = [];
  private string $dimension, $metric;

  public function __construct(string $dimension, string $metric) {
    $this->dimension = $dimension;
    $this->metric = $metric;
  }

  public function add($dimension, $metric) {
    $this->report[] = [
      'dimensions' => [
        $this->dimension => $dimension
      ],
      'metrics' => [
        $this->metric => $metric
      ]
    ];
  }

  public function get(): array {
    return $this->report;
  }

}
