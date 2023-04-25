<?php namespace App\Models;

use Jenssegers\Mongodb\Eloquent\Model;

/**
 * Historic data. PhoenixGamblingSportData model deletes itself once in a while.
 */
class PhoenixGamblingSportHistory extends Model {

  protected $connection = 'mongodb';
  protected $collection = 'phoenix_gambling_sport_history';

  /**
   * The attributes that are mass assignable.
   *
   * @var array
   */
  protected $fillable = [
    'srId', 'sportId', 'sportName', 'categoryId', 'categoryName', 'tournamentId', 'tournamentName', 'home', 'away',
    'matchStatus', 'scheduledTime', 'time', 'score', 'status', 'markets', 'eSport'
  ];

}
