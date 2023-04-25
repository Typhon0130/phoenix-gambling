<?php

namespace App\Console\Commands;

use App\Currency\Currency;
use App\Models\SportBet;
use App\Models\Transaction;
use App\Models\User;
use App\Sport\Sport;
use Carbon\Carbon;
use Illuminate\Console\Command;

class LeaderboardRewards extends Command {

  /**
   * The name and signature of the console command.
   *
   * @var string
   */
  protected $signature = 'phoenix:leaderboardRewards';

  /**
   * The console command description.
   *
   * @var string
   */
  protected $description = 'Processes leaderboard rewards';

  /**
   * Execute the console command.
   *
   * @return mixed
   */
  public function handle() {
    $give = function($leaderboardEntry, $amount) {
      $currency = Currency::all()[0];
      if($currency == null) return;

      $user = User::where('_id', $leaderboardEntry['entry']['user'])->first();
      $user?->balance($currency)->add($currency->convertUSDToToken($amount), Transaction::builder()->message('Competition Reward')->get());
    };

    // Daily
    $leaderboard = \App\Models\Leaderboard::getLeaderboard('today');
    if(isset($leaderboard[0])) $give($leaderboard[0], 24);
    if(isset($leaderboard[1])) $give($leaderboard[1], 14);
    if(isset($leaderboard[2])) $give($leaderboard[2], 10);
    if(isset($leaderboard[3])) $give($leaderboard[3], 6);
    if(isset($leaderboard[4])) $give($leaderboard[4], 4);

    // Weekly
    if(now()->startOfDay()->timestamp === now()->startOfWeek()->timestamp) {
      $leaderboard = \App\Models\Leaderboard::getLeaderboard('week');
      if(isset($leaderboard[0])) $give($leaderboard[0], 120);
      if(isset($leaderboard[1])) $give($leaderboard[1], 80);
      if(isset($leaderboard[2])) $give($leaderboard[2], 50);
      if(isset($leaderboard[3])) $give($leaderboard[3], 30);
      if(isset($leaderboard[4])) $give($leaderboard[4], 20);
    }

    // Monthly
    if(now()->day + 1 === 1) {
      $leaderboard = \App\Models\Leaderboard::getLeaderboard('month');
      if(isset($leaderboard[0])) $give($leaderboard[0], 400);
      if(isset($leaderboard[1])) $give($leaderboard[1], 200);
      if(isset($leaderboard[2])) $give($leaderboard[2], 150);
      if(isset($leaderboard[3])) $give($leaderboard[3], 100);
      if(isset($leaderboard[4])) $give($leaderboard[4], 50);
      if(isset($leaderboard[5])) $give($leaderboard[5], 30);
      if(isset($leaderboard[6])) $give($leaderboard[6], 25);
      if(isset($leaderboard[7])) $give($leaderboard[7], 20);
      if(isset($leaderboard[8])) $give($leaderboard[8], 15);
      if(isset($leaderboard[9])) $give($leaderboard[9], 10);
    }

    return 1;
  }

}
