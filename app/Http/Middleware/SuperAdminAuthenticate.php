<?php

namespace App\Http\Middleware;

use App\Permission\RootPermission;
use Closure;

class SuperAdminAuthenticate {

  /**
   * Handle an incoming request.
   *
   * @param  \Illuminate\Http\Request  $request
   * @param  \Closure  $next
   * @return mixed
   */
  public function handle($request, Closure $next) {
    if(env('VITE_IS_DEMO') || (!auth('sanctum')->guest() && auth('sanctum')->user()->checkPermission(new RootPermission()))) return $next($request);
    return response('', 403);
  }

}
