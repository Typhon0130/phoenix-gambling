<?php namespace App\ActivityLog;

use App\Models\AdminActivity;

class HideGameActivity extends ActivityLogEntry {

  public function id() {
    return "hide";
  }

  protected function format(AdminActivity $data) {
    return $data->data['api_id'] . ' is now ' . ($data->data['state'] ? 'hidden' : 'visible');
  }

}
