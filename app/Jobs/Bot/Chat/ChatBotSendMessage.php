<?php

namespace App\Jobs\Bot\Chat;

use App\Models\Settings;
use App\Models\User;
use Faker\Factory;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;

class ChatBotSendMessage implements ShouldQueue {

  use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

  public function handle() {
    $faker = Factory::create();
    $username = $faker->userName;

    $user = User::create([
      'name' => $username,
      'password' => Hash::make(uniqid()),
      'avatar' => '/avatar/' . uniqid(),
      'email' => null,
      'client_seed' => \App\Games\Kernel\ProvablyFair::generateServerSeed(),
      'roles' => [],
      'name_history' => [['time' => \Carbon\Carbon::now(), 'name' => $username]],
      'register_ip' => null,
      'login_ip' => null,
      'bot' => true,
      'register_multiaccount_hash' => null,
      'login_multiaccount_hash' => null,
      'private_profile' => true
    ]);

    $messages = preg_split('/\r\n|\n\r|\r|\n/', Settings::get('Messages'));
    $channels = preg_split('/\r\n|\n\r|\r|\n/', Settings::get('Chat bot channel'));

    foreach ($channels as $channel) {
      $message = \App\Models\Chat::create([
        'user' => $user->toArray(),
        'user_id' => $user->_id,
        'vipLevel' => 0,
        'data' => $messages[mt_rand(0, count($messages) - 1)],
        'type' => 'message',
        'channel' => $channel,
        'bot' => true
      ]);

      event(new \App\Events\ChatMessage($message));
    }
  }

}
