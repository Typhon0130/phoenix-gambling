<?php namespace App\ActivityLog;

use App\Models\AdminActivity;

class ChatUnmutelog extends ActivityLogEntry {

    public function id() {
        return 'chat_unmute';
    }

    protected function format(AdminActivity $data) {
        return 'Unmuted @'.$data->data['id'];
    }

}
