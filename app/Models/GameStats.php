<?php

namespace App\Models;

use Jenssegers\Mongodb\Eloquent\Model;

class GameStats extends Model {

    protected $connection = 'mongodb';
    protected $collection = 'game_stats';

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'game', 'launches'
    ];

}
