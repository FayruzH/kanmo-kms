<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>KMS</title>
  @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body>
  <nav class="navbar navbar-expand-lg border-bottom">
    <div class="container py-2">
      <a class="navbar-brand fw-bold" href="/">KMS</a>

      <div class="ms-auto d-flex align-items-center gap-2">
        @auth
          <span class="small opacity-75">Hi, {{ auth()->user()->name }}</span>
          <form method="POST" action="{{ route('logout') }}" class="m-0">
            @csrf
            <button class="btn btn-sm btn-outline-secondary">Logout</button>
          </form>
        @else
          <a class="btn btn-sm btn-outline-primary" href="{{ route('login') }}">Login</a>
        @endauth
      </div>
    </div>
  </nav>

  <main class="py-4">
    @yield('content')
  </main>
</body>
</html>
