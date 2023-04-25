<?php namespace App\Permission;

class ManageTicketsPermission extends Permission {

  public function id(): string {
    return "manageTickets";
  }

  public function name(): string {
    return "Ticket moderator";
  }

  public function description(): string {
    return "Allows to answer user tickets.";
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
