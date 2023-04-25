<!DOCTYPE html>
<html>
  <head>
    <title>{{ \App\Models\Settings::get('[OpenGraph] Title', 'Website Title') }}</title>

    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no, height=device-height, minimum-scale=1, maximum-scale=1, user-scalable=no">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    @if(env('APP_DEBUG'))
      <meta http-equiv="Expires" content="Mon, 26 Jul 1997 05:00:00 GMT">
      <meta http-equiv="Pragma" content="no-cache">
    @endif

    <link rel="icon" href="{{ asset('/favicon.png') }}">

    @php \App\Utils\ViteHotReloadFile::set('@admin'); @endphp
    @vite('resources/app.js', 'build/@admin')
  </head>
  <body>
    <div id="app"></div>
  </body>
</html>
