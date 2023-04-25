<?php

namespace App\Models;

use Illuminate\Support\Facades\Cache;
use Jenssegers\Mongodb\Eloquent\Model;

class Settings extends Model {

  protected $connection = 'mongodb';
  protected $collection = 'settings';

  /**
   * The attributes that are mass assignable.
   *
   * @var array
   */
  protected $fillable = [
    'name', 'description', 'value'
  ];

  /**
   * The attributes that should be hidden for arrays.
   *
   * @var array
   */
  protected $hidden = [];

  public static function get(string $key, string $default = '') {
    $value = Settings::where('name', $key)->first();
    if (!$value) {
      Settings::create([
        'name' => $key,
        'value' => $default
      ]);
      return $default;
    }

    return $value->value;
  }

  public static function set($key, $value) {
    Cache::forget('settings:' . $key);

    $setting = Settings::where('name', $key);

    if (!$setting->first()) Settings::create(['name' => $key, 'value' => $value]);
    else $setting->update(['value' => $value]);
  }

  public static function toggle($key, $x = 'false', $y = 'true', $default = 'false') {
    $value = self::get($key, $default);
    self::set($key, $value === $x ? $y : $x);
  }

}
