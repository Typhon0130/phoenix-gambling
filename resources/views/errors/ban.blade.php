@include('errors.error', ['code' => 'Your account was banned', 'desc' => 'Contact support for more info. Your name: ' . (auth('sanctum')->guest() ? 'unknown' : auth('sanctum')->user()->name)])
