<?php

namespace App\Models;

use Jenssegers\Mongodb\Eloquent\Model;

class GameAnalytics extends Model {

  protected $connection = 'mongodb';
  protected $collection = 'game_analytics';

  /**
   * The attributes that are mass assignable.
   *
   * @var array
   */
  protected $fillable = [
    'statId', 'type', 'currency', 'wagered', 'payout', 'profit'
  ];

}

