<?php

namespace App\Models;

use App\Sport\Provider\SportGameSnapshot;
use App\Sport\Sport;
use Exception;
use Illuminate\Support\Facades\Log;
use Jenssegers\Mongodb\Eloquent\Model;

class SportBet extends Model {

  protected $connection = 'mongodb';
  protected $collection = 'sport_bets';

  protected $fillable = [
    'user', 'sportradar_id', 'status', 'game_id', 'market', 'runner', 'odds', 'bet', 'currency', 'category', 'icon', 'game',
    'snapshot', 'multiBetId', 'sportType'
  ];

  /**
   * @throws Exception if $status is not skip/lose/win/refund
   */
  public function validate($forceSetStatus = null): bool {
    $user = User::where('_id', $this->user)->first();
    if(!$user) return false;

    if($this->multiBetId !== null) {
      $payout = 0;

      $bets = SportBet::where('multiBetId', $this->multiBetId)->get();
      foreach ($bets as $bet) {
        if($payout === 0) $payout = $bet->odds;
        else $payout *= $bet->odds;

        $snapshot = SportGameSnapshot::fromArray($bet->snapshot);
        $market = Sport::getLine()->findMarket($bet->sportType, $bet->market, $bet->runner);

        if($bet->sportType === 'SPORTS') {
          if ($forceSetStatus == null && !$market->getData($snapshot->id())->match()->isFinished()) return false;
        } else if($bet->sportType === 'ESPORTS') {
          if($forceSetStatus == null && $market->findHistoricGame($snapshot->id())->matchStatus !== 'Closed') return false;
        }

        $status = $forceSetStatus == null ? $market->isWinner($bet->runner, $snapshot) : $forceSetStatus;

        if($status !== 'win') {
          if($status === 'lose' || $status === 'refund') {
            foreach ($bets as $b) {
              $b->update(['status' => $status]);
            }
          }

          return false;
        } else $bet->update(['status' => 'win']);
      }

      foreach ($bets as $bet) {
        $bet->update(['status' => 'win']);
      }

      $user->balance(\App\Currency\Currency::find($this->currency))->add($this->bet * $payout, Transaction::builder()->message('Sport [Win] [Multibet id: ' . $this->multiBetId . ']')->get());
      return true;
    }

    $market = Sport::getLine()->findMarket($this->sportType, $this->market, $this->runner);
    if (!$market) return false;

    $snapshot = SportGameSnapshot::fromArray($this->snapshot);

    if($this->sportType === 'SPORTS') {
      if ($forceSetStatus == null && !$market->getData($snapshot->id())->match()->isFinished()) return false;
    } else if($this->sportType === 'ESPORTS') {
      if($forceSetStatus == null && $market->findHistoricGame($snapshot->id())->matchStatus !== 'Closed') return false;
    }

    $status = $forceSetStatus == null ? $market->isWinner($this->runner, $snapshot) : $forceSetStatus;

    if($status === 'skip') return false;

    $allowed = ['skip', 'refund', 'win', 'lose'];
    if(!in_array($status, $allowed)) throw new Exception('Unknown handler status result: ' . $status);

    $this->update([
      'status' => $status
    ]);

    if ($status === 'refund')
      $user->balance(\App\Currency\Currency::find($this->currency))->add($this->bet, Transaction::builder()->message('Sport [Refund] ' . $this->game . ' ' . $this->market . ' ' . $this->runner . ' ' . $this->odds)->get());
    else if ($status === 'win')
      $user->balance(\App\Currency\Currency::find($this->currency))->add($this->bet * $this->odds, Transaction::builder()->message('Sport [Win] ' . $this->game . ' ' . $this->market . ' ' . $this->runner . ' ' . $this->odds)->get());

    return true;
  }

}
