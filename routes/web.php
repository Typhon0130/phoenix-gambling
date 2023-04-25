<?php

use App\Games\Kernel\ThirdParty\ThirdPartyGame;
use App\Utils\Demo;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Cookie;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Route;

Route::get('/avatar/{hash}', function ($hash) {
  $size = 100;
  $icon = new \Jdenticon\Identicon();
  $icon->setValue($hash);
  $icon->setSize($size);

  $style = new \Jdenticon\IdenticonStyle();
  $style->setBackgroundColor('#21232a');
  $icon->setStyle($style);

  $icon->displayImage('png');
  return response('')->header('Content-Type', 'image/png');
});

Route::post('/license', function() {
  $license = new \App\License\License();

  if(!$license->isValid()) return [ 'isValid' => false ];

  $pluginIds = [];

  foreach ((new \App\License\Plugin\PluginManager())->fetch() as $item)
    if($item->isEnabled())
      $pluginIds[] = $item->id();

  return [
    'isValid' => $license->isValid(),
    'enabledFeatures' => $license->features(),
    'plugins' => $pluginIds
  ];
});

Route::any('/installer/firstTimeUpdate', function() {
  if(\App\Models\Settings::get('[Installer] First time update', 'false') !== 'false') return \App\Utils\APIResponse::reject(1, 'Invalid state');
  \App\Models\Settings::set('[Installer] First time update', 'true');
  return (new \App\Updater\Updater())->update();
});

Route::prefix('internal')->middleware('internal')->group(function() {
  Route::get('license', function() {
    return \App\Utils\APIResponse::success([ 'key' => (new \App\License\License())->getKey() ]);
  });
});

Route::get('/{url?}', function ($url = null) {
  if($url && str_starts_with($url, "admin")) {
    if(!Demo::isDemo(true) && (auth('sanctum')->guest() || !auth('sanctum')->user()->checkPermission(new \App\Permission\DashboardPermission()))) return view('errors.403');
    return view('layouts.admin');
  }

  return view('layouts.app');
})->where('url', '[ \/\w\:.-]*');
