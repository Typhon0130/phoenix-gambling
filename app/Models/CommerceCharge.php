<?php namespace App\Models;


use Jenssegers\Mongodb\Eloquent\Model;

class CommerceCharge extends Model {

  protected $connection = 'mongodb';
  protected $collection = 'commerce_charges';

  /**
   * The attributes that are mass assignable.
   *
   * @var array
   */
  protected $fillable = [
    'user', 'currency', 'address', 'code', 'gotPayment'
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
  protected $casts = [];

}
