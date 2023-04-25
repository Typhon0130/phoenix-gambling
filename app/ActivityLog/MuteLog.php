<?php namespace App\ActivityLog;

use App\Models\AdminActivity;

class MuteLog extends ActivityLogEntry {

    public function id() {
        return 'mute';
    }

    protected function format(AdminActivity $data) {
        return 'Muted @'.$data->data['id'].' for '.$data->data['minutes'].' minute(s)';
    }

}
