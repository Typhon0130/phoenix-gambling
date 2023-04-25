<?php namespace App\Sport\Provider\PhoenixGambling;

use App\Sport\Provider\SportLiveStatus;
use Carbon\Carbon;

class PhoenixGamblingLiveStatus extends SportLiveStatus {

  private string $status, $time, $score, $scheduledTime;

  public function __construct(string $status, string $time, string $score, string $scheduledTime) {
    $this->status = $status;
    $this->time = $time;
    $this->score = $score;
    $this->scheduledTime = $scheduledTime;
  }

  public function stage(): string {
    return $this->status;
  }

  public function score(): string {
    return $this->score === '' ? '-:-' : str_replace("-", ":", $this->score);
  }

  public function setScores(): string {
    return $this->time;
  }

  public function createdAt(): int {
    return Carbon::createFromFormat('Y-m-d H:i:s', $this->scheduledTime)->getTimestamp() * 1000;
  }

}
