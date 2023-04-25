<?php namespace App\Permission;

class IgnorePrivacyPermission extends Permission {

    public function id(): string {
        return "ignore_privacy";
    }

    public function name(): string {
        return "Ignore bet & user profile privacy";
    }

    public function description(): string {
        return "Show player name regardless of their privacy settings.";
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
