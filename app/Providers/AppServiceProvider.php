<?php

namespace App\Providers;

use App\Token\PersonalAccessToken;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\ServiceProvider;
use Laravel\Sanctum\Sanctum;

class AppServiceProvider extends ServiceProvider {

    /**
     * Register any application services.
     * @return void
     */
    public function register() {
        //
    }

    /**
     * Bootstrap any application services.
     * @return void
     */
    public function boot() {
        //URL::forceScheme('https');

        Sanctum::usePersonalAccessTokenModel(PersonalAccessToken::class);
    }

}
