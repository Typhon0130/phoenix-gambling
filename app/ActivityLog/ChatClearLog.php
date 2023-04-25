<?php namespace App\ActivityLog;

use App\Models\AdminActivity;

class ChatClearLog extends ActivityLogEntry {

    public function id() {
        return 'chat_clear';
    }

    protected function format(AdminActivity $data) {
        if($data->data['type'] === 'all') return 'Removed all messages in chat from @'.$data->data['id'];
        else return 'Removed chat message from @'.$data->data['id'];
    }

}
