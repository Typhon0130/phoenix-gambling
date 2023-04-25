<?php namespace App\Permission;

class SportManagementPermission extends Permission {

  public function id(): string {
    return "sportManagement";
  }

  public function name(): string {
    return "Sport management";
  }

  public function description(): string {
    return "Allows to refund, cancel and change status of sport bets.";
  }

  public function isEditable(): bool {
    return false;
  }

  public function isDeletable(): bool {
    return false;
  }

  public function isCreatable(): bool {
    return false;
  }

}
