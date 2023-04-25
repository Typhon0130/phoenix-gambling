<?php namespace App\Http\Middleware;

use Carbon\Carbon;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cookie;

class MultiaccountCheck {

    /**
     * Handle an incoming request.
     *
     * @param Request $request
     * @param Closure $next
     * @return mixed
     */
    public function handle(Request $request, Closure $next): mixed {
        if(!Cookie::has('s')) Cookie::queue(Cookie::make('s', uniqid().Carbon::now()->timestamp, 525600 /* 1 year */));
        return $next($request);
    }

}
