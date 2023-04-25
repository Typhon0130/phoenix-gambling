<?php namespace App\Models;

use Jenssegers\Mongodb\Eloquent\Model;

class PhoenixGamblingSportData extends Model {

  protected $connection = 'mongodb';
  protected $collection = 'phoenix_gambling_sport_data';

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
