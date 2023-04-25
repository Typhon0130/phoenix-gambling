<?php namespace App\Events;

use App\Currency\Currency;
use App\Models\BonusBalance;
use App\Models\User;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class BonusBalanceTransferred implements ShouldBroadcastNow {

  use Dispatchable, InteractsWithSockets, SerializesModels;

  private User $user;
  private BonusBalance $bonusBalance;

  public function __construct($user, BonusBalance $bonusBalance = null) {
    $this->user = $user;
    $this->bonusBalance = $bonusBalance;
  }

  /**
   * Get the channels the event should broadcast on.
   *
   * @return Channel
   */
  public function broadcastOn() {
    return new PrivateChannel('App.Models.User.'.$this->user->id);
  }

  public function broadcastWith() {
    return $this->bonusBalance->toArray();
  }

}
