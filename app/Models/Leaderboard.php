<?php namespace App\Models;

use App\Utils\Exception\UnsupportedOperationException;
use Carbon\Carbon;
use Jenssegers\Mongodb\Eloquent\Model;

class Leaderboard extends Model {

  protected $connection = 'mongodb';
  protected $collection = 'competition';

  /**
   * The attributes that are mass assignable.
   *
   * @var array
   */
  protected $fillable = [
    'time', 'user', 'wager_usd'
  ];

  public static function insert(Game $game) {
    if($game->status === 'in-progress' || $game->status === 'cancelled' || $game->demo || $game->multiplier === 1) return;

    self::insertGame($game);
  }

  private static function insertGame(Game $game) {
    $currency = \App\Currency\Currency::find($game->currency);

    $entry = Leaderboard::where('user', $game->user)->where('time', '>=', self::toTime('today'))->first();

    if(!$entry) {
      Leaderboard::create([
        'wager_usd' => $currency->convertTokenToUSD($game->wager),
        'time' => self::toTime('today'),
        'user' => $game->user
      ]);
      return;
    }

    $entry->update([
      'wager_usd' => $entry->wager_usd + $currency->convertTokenToUSD($game->wager)
    ]);
  }

  public static function getLeaderboard(string $type): array {
    $result = [];
    foreach(Leaderboard::where('time', '>=', self::toTime($type))->orderBy('wager_usd', 'desc')->take(10)->get() as $entry) {
      $result[] = [
        'entry' => $entry->toArray(),
        'user' => User::where('_id', $entry->user)->first()->toArray()
      ];
    }
    return $result;
  }

  private static function toTime($type): int {
    return match ($type) {
      'today' => Carbon::today()->timestamp,
      'week' => Carbon::now()->startOfWeek()->timestamp,
      'month' => Carbon::now()->startOfMonth()->timestamp,
      default => throw new UnsupportedOperationException('Invalid leaderboard type: ' . $type),
    };
  }

}
