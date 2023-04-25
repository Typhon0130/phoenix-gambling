<?php namespace App\Permission;

namespace App\Permission;

class ChatModeratorPermission extends Permission {

    public function id(): string {
        return "chat_moderator";
    }

    public function name(): string {
        return "Moderator";
    }

    public function description(): string {
        return "Mute & delete chat messages. Blue border is added to own messages only if user has system chat moderator role.";
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
