<?php namespace App\Permission;

class NotificationPermission extends Permission {

    public function id(): string {
        return "notifications";
    }

    public function name(): string {
        return "Notifications";
    }

    public function description(): string {
        return "Notifications.";
    }

    public function isEditable(): bool {
        return false;
    }

    public function isDeletable(): bool {
        return true;
    }

    public function isCreatable(): bool {
        return true;
    }

}
