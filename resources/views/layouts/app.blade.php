<!DOCTYPE html>
<html>
  <head>
    <title>{{ \App\Models\Settings::get('[OpenGraph] Title', 'Website Title')  }}</title>

    <meta charset="utf-8">
    <meta name="viewport"
          content="width=device-width, initial-scale=1, shrink-to-fit=no, height=device-height, minimum-scale=1, maximum-scale=1, user-scalable=no">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <meta property="og:image" content="{{ \App\Models\Settings::get('[OpenGraph] Image URL', '/img/phoenix_preview.png') }}"/>
    <meta property="og:image:secure_url" content="{{ \App\Models\Settings::get('[OpenGraph] Image URL', '/img/phoenix_preview.png') }}"/>
    <meta property="og:image:type" content="image/png"/>
    <meta property="og:image:width" content="295"/>
    <meta property="og:image:height" content="295"/>
    <meta property="og:site_name" content="{{ \App\Models\Settings::get('[OpenGraph] Title', 'Website Title')  }}"/>

    @if(env('APP_DEBUG'))
      <meta http-equiv="Expires" content="Mon, 26 Jul 1997 05:00:00 GMT">
      <meta http-equiv="Pragma" content="no-cache">
    @endif

    <link rel="icon" href="{{ asset('/favicon.png') }}">

    @php \App\Utils\ViteHotReloadFile::set('@web'); @endphp
    @vite('resources/js/app.js', 'build/@web')

    <link rel="stylesheet" href="/css/sportradar-widget.css">
  </head>
  <body>
    <div id="app">
      <layout></layout>
    </div>

    @if(\App\Models\Settings::get('[GA] Configured', 'null') === 'true')
      @php
        $streamId = \App\Models\Settings::get('[GA] Measurement ID');
      @endphp

      <script async src="https://www.googletagmanager.com/gtag/js?id={{ $streamId }}"></script>
      <script>
        window.dataLayer = window.dataLayer || [];
        function gtag(){dataLayer.push(arguments);}
        gtag('js', new Date());

        gtag('config', '{{ $streamId }}');
      </script>
    @endif

    @if((new \App\License\License())->hasFeature('phoenixSport'))
      <script type="text/javascript">
        (function(a,b,c,d,e,f,g,h,i){a[e]||(i=a[e]=function(){(a[e].q=a[e].q||[]).push(arguments)},i.l=1*new Date,i.o=f,
            g=b.createElement(c),h=b.getElementsByTagName(c)[0],g.async=1,g.src=d,g.setAttribute("n",e),h.parentNode.insertBefore(g,h)
        )})(window,document,"script","https://phoenix-gambling.com:2053/widget/loader?url=https://widgets.sir.sportradar.com/sportradar/widgetloader","SIR", {
          language: 'en'
        });
      </script>

      <!--suppress JSUnresolvedLibraryURL -->
      <script src="https://embed.twitch.tv/embed/v1.js"></script>
    @endif
  </body>
</html>
