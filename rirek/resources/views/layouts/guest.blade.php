<!doctype html>
<html lang="ja">
  <head>
    <meta charset="utf-8"/>
    <meta name="viewport" content="width=device-width,initial-scale=1"/>
    <title>{{ config('app.name','RirekishoMaker') }}</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@picocss/pico@2/css/pico.min.css">
    <style>.container{max-width:640px;margin:auto;padding:2rem 1rem}</style>
  </head>
  <body>
    <main class="container">
      {{ $slot ?? '' }}
      @yield('content')
    </main>
  </body>
</html>
