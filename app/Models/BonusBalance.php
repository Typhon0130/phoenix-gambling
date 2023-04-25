<?php namespace App\Models;

use Jenssegers\Mongodb\Eloquent\Model;

class BonusBalance extends Model {

  protected $connection = 'mongodb';
  protected $collection = 'bonus_balances';

  /**
   * The attributes that are mass assignable.
   *
   * @var array
   */
  protected $fillable = [
    'user',
    'currency',
    'originalValue',
    'value',
    'wagered',
    'neededToWager',
    'description'
  ];

  /**
   * The attributes that should be hidden for arrays.
   *
   * @var array
   */
  protected $hidden = [
  ];

  /**
   * The attributes that should be cast to native types.
   *
   * @var array
   */
  protected $casts = [
  ];

}
