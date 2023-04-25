<?php namespace App\Permission;

class BannerPermission extends Permission {

    public function id(): string {
        return "banner";
    }

    public function name(): string {
        return "Banner";
    }

    public function description(): string {
        return "Allows to edit banner contents which shows immediately after page is loaded.";
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
