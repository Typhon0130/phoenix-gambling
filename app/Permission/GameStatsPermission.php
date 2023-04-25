<?php namespace App\Permission;

class GameStatsPermission extends Permission {

  public function id(): string {
    return "game_stats";
  }

  public function name(): string {
    return "Game analytics";
  }

  public function description(): string {
    return "Allows to view game statistics like daily/total wager, profit, etc.";
  }

  public function isEditable(): bool {
    return false;
  }

  public function isViewable(): bool {
    return false;
  }

  public function isDeletable(): bool {
    return false;
  }

  public function isCreatable(): bool {
    return false;
  }

}
