<?php

namespace App\Http\Middleware;

use App\Permission\DashboardPermission;
use App\Utils\Demo;
use Closure;
use Illuminate\Support\Facades\Log;

class AdminAuthenticate {

  /**
   * Handle an incoming request.
   *
   * @param \Illuminate\Http\Request $request
   * @param \Closure $next
   * @return mixed
   */
  public function handle($request, Closure $next) {
    if (Demo::isDemo(true) || (!auth('sanctum')->guest() && auth('sanctum')->user()->checkPermission(new DashboardPermission()))) return $next($request);
    return response('', 403);
  }

}
