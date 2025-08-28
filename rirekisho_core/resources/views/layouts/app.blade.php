<!DOCTYPE html>
<html lang="ja">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>@yield('title', 'Rirekisho Maker | IT-Future')</title>

  {{-- Bootstrap --}}
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">

  {{-- Custom CSS --}}
  <link href="{{ asset('css/rirekisho.css') }}" rel="stylesheet">
</head>
<body class="bg-light">

  {{-- Navbar --}}
  <nav class="navbar navbar-expand-lg navbar-dark bg-dark">
    <div class="container">
      <a class="navbar-brand" href="https://it-future.jp">IT-Future</a>
      <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navMenu">
        <span class="navbar-toggler-icon"></span>
      </button>
      <div class="collapse navbar-collapse" id="navMenu">
        <ul class="navbar-nav ms-auto">
          <li class="nav-item">
            <a href="{{ route('rirekisho.landing') }}" class="nav-link">Home</a>
          </li>
          <li class="nav-item">
            <a href="{{ route('rirekisho.step1') }}" class="nav-link">Start</a>
          </li>
          <li class="nav-item">
            <a href="https://it-future.jp/#contact" class="nav-link">Contact</a>
          </li>
        </ul>
      </div>
    </div>
  </nav>

  {{-- Main Content --}}
  <main>
    @yield('content')
  </main>

  {{-- Footer --}}
  <footer class="bg-dark text-white mt-5">
    <div class="container py-4 text-center small">
      <div>© 2025 IT-Future. All rights reserved.</div>
      <div class="mt-2">
        <a href="https://it-future.jp" class="text-white-50 text-decoration-none">Main Site</a>
        <span class="text-white-50 mx-2">•</span>
        <a href="https://it-future.jp/#contact" class="text-white-50 text-decoration-none">お問い合わせ</a>
      </div>
    </div>
  </footer>

  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
