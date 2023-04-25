<?php namespace App\Events;

use App\Models\Game;
use App\Sport\Sport;
use App\Models\SportBet;
use App\Models\User;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class LiveFeedSportGame implements ShouldBroadcastNow {

  use Dispatchable, InteractsWithSockets, SerializesModels;

  private SportBet $game;
  private $delay;

  public function __construct(SportBet $game) {
    $this->game = $game;
  }

  /**
   * Get the channels the event should broadcast on.
   *
   * @return Channel
   */
  public function broadcastOn() {
    return new Channel('Everyone');
  }

  public function broadcastWith() {
    return [
      'game' => $this->game->toArray(),
      'user' => User::where('_id', $this->game->user)->first()->toArray()
    ];
  }

}
