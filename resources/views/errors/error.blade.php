<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}">
  <head>
    <title>demo.phoenix-gambling.com - {{ $code ?? -1 }}</title>

    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">

    <link rel="icon" href="/favicon.png">
    <link rel="stylesheet" href="/error.css">
  </head>
  <body>
    <div class="code">
      <div>{{ $code ?? -1 }}</div>
      <div>{{ $desc ?? 'An error has occurred' }}</div>
    </div>
  </body>
</html>
