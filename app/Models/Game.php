<?php

namespace App\Models;

use Jenssegers\Mongodb\Eloquent\Model;

class Game extends Model {

    protected $connection = 'mongodb';
    protected $collection = 'games';

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'id', 'client_seed', 'server_seed', 'nonce', 'user', 'game', 'gameName', 'multiplier', 'status', 'profit', 'data', 'type', 'demo',
        'wager', 'currency', 'ss_gameId', 'sentUpdate', 'bet_usd_converted', 'profit_usd_converted'
    ];

    /**
     * The attributes that should be hidden for arrays.
     *
     * @var array
     */
    protected $hidden = [];

    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    protected $casts = [
        'data' => 'json'
    ];

}
