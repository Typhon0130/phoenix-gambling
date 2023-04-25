<?php

namespace App\Http\Middleware;

use App\Models\User;
use App\Permission\DashboardPermission;
use App\Utils\Demo;
use Closure;
use Illuminate\Support\Facades\Log;

class InternalMiddleware {

  /**
   * Handle an incoming request.
   *
   * @param \Illuminate\Http\Request $request
   * @param \Closure $next
   * @return mixed
   */
  public function handle($request, Closure $next) {
    $localIps = [
      '127.0.0.1',
      'localhost',
      '::1'
    ];

    if(in_array(User::getIp(false), $localIps) || User::getIp() === User::getServerIp()) return $next($request);
    return response('', 403);
  }

}
