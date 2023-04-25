<?php

namespace App\Http\Middleware;

use App\Permission\ChatModeratorPermission;
use App\Utils\Demo;
use Closure;

class ModeratorAuthenticate {

  /**
   * Handle an incoming request.
   *
   * @param \Illuminate\Http\Request $request
   * @param \Closure $next
   * @return mixed
   */
  public function handle($request, Closure $next) {
    if (Demo::isDemo(true) || !auth('sanctum')->guest() && auth('sanctum')->user()->checkPermission(new ChatModeratorPermission())) return $next($request);
    return response('', 403);
  }

}
