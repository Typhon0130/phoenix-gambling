<?php

namespace App\Jobs\Bot;

use App\Models\Settings;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class BotScheduler implements ShouldQueue {

  use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

  public function handle() {
    if (Settings::get('[Bet Bot] Stop', 'true') === 'true') return;

    $games = mt_rand(floatval(Settings::get('Min. amount of games from one bot', 20)), floatval(Settings::get('Max. amount of games from one bot', 50)));
    dispatch(new BotNewAccount($games, floatval(Settings::get('Min. delay between games (seconds)', 1)), floatval(Settings::get('Max. delay between games (seconds)', 5))));

    dispatch((new BotScheduler())->delay(now()->addSeconds(floatval(Settings::get('Create new bot every X seconds', 20)))));
  }

}
