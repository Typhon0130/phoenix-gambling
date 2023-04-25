<?php namespace App\WebSocket;

class OnlineUsersWhisper extends WebSocketWhisper {

  public function event(): string {
    return 'OnlineUsers';
  }

  public function process($data): array {
    $users = [];
    foreach (\App\ActivityLog\ActivityLogEntry::onlineUsers() as $user) $users[] = $user->name;
    return $users;
  }

}
