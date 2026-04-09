<!doctype html>
<html lang="en" data-theme="light">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Kanmo KMS</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
  @vite(['resources/css/app.css', 'resources/js/app.js'])
  <style>
    @media (min-width: 993px) {
      body.kms-density-80.kms-density-zoom .kms-shell {
        min-height: calc(100vh / 0.8);
      }

      body.kms-density-80.kms-density-zoom .kms-sidebar {
        height: calc(100vh / 0.8);
      }

      body.kms-density-80.kms-density-layout .kms-shell {
        grid-template-columns: 232px 1fr;
      }

      body.kms-density-80.kms-density-layout .kms-brand {
        padding: 20px 18px;
      }

      body.kms-density-80.kms-density-layout .kms-brand-title {
        font-size: 1.4rem;
      }

      body.kms-density-80.kms-density-layout .kms-brand-sub {
        font-size: 0.85rem;
      }

      body.kms-density-80.kms-density-layout .kms-nav-link {
        padding: 10px 12px;
      }

      body.kms-density-80.kms-density-layout .kms-nav-link i {
        font-size: 1.15rem;
      }

      body.kms-density-80.kms-density-layout .kms-user {
        padding: 14px 18px;
      }

      .kms-shell.sidebar-collapsed {
        grid-template-columns: 90px 1fr !important;
      }

      body.kms-density-80.kms-density-layout .kms-shell.sidebar-collapsed {
        grid-template-columns: 72px 1fr !important;
      }

      .kms-shell.sidebar-collapsed .kms-brand {
        justify-content: center;
        padding-left: 0;
        padding-right: 0;
      }

      .kms-shell.sidebar-collapsed .kms-brand-copy,
      .kms-shell.sidebar-collapsed .kms-nav-text,
      .kms-shell.sidebar-collapsed .kms-user-meta {
        opacity: 0 !important;
        transform: translateX(-8px) !important;
        max-width: 0 !important;
        pointer-events: none !important;
      }

      .kms-shell.sidebar-collapsed .kms-nav {
        padding-left: 10px;
        padding-right: 10px;
      }

      .kms-shell.sidebar-collapsed .kms-nav-link {
        justify-content: center;
        padding-left: 0;
        padding-right: 0;
      }

      .kms-shell.sidebar-collapsed .kms-user {
        justify-content: center;
        padding-left: 0;
        padding-right: 0;
      }
    }
  </style>
</head>
<body class="{{ auth()->check() ? 'kms-density-80' : '' }}">
  @if(auth()->check() && auth()->user()->role === 'admin' && !request()->routeIs('employee.*'))
    <div class="kms-shell kms-can-collapse">
      <aside class="kms-sidebar border-end">
        <div class="kms-brand">
          <div class="kms-brand-logo">K</div>
          <div class="kms-brand-copy">
            <div class="kms-brand-title">Kanmo KMS</div>
            <div class="kms-brand-sub">Knowledge Management</div>
          </div>
        </div>

        <nav class="kms-nav">
          <a href="{{ route('admin.overview') }}" class="kms-nav-link {{ request()->routeIs('admin.overview') ? 'active' : '' }}">
            <i class="bi bi-layout-text-window-reverse"></i><span class="kms-nav-text">Dashboard</span>
          </a>
          <a href="{{ route('admin.sop.index') }}" class="kms-nav-link {{ request()->routeIs('admin.sop.*') && !request()->routeIs('admin.sop.import.*') && !request()->routeIs('admin.sop.expired.*') ? 'active' : '' }}">
            <i class="bi bi-file-earmark-text"></i><span class="kms-nav-text">SOP Management</span>
          </a>
          <a href="{{ route('admin.sop.import.index') }}" class="kms-nav-link {{ request()->routeIs('admin.sop.import.*') ? 'active' : '' }}">
            <i class="bi bi-upload"></i><span class="kms-nav-text">Bulk Import</span>
          </a>
          <a href="{{ route('admin.sop.expired.index') }}" class="kms-nav-link {{ request()->routeIs('admin.sop.expired.*') ? 'active' : '' }}">
            <i class="bi bi-exclamation-triangle"></i><span class="kms-nav-text">Expired SOPs</span>
          </a>
          <a href="{{ route('admin.analytics.index') }}" class="kms-nav-link {{ request()->routeIs('admin.analytics.*') ? 'active' : '' }}">
            <i class="bi bi-bar-chart"></i><span class="kms-nav-text">Analytics</span>
          </a>
          <a href="{{ route('admin.settings.index') }}" class="kms-nav-link {{ request()->routeIs('admin.settings.*') ? 'active' : '' }}">
            <i class="bi bi-gear"></i><span class="kms-nav-text">Settings</span>
          </a>
        </nav>

        <div class="kms-user">
          <details class="kms-profile-menu">
            <summary class="kms-profile-trigger">
              <div class="kms-user-avatar">{{ strtoupper(substr(auth()->user()->name, 0, 1)) }}</div>
              <div class="kms-user-meta">
                <div class="fw-semibold">{{ auth()->user()->name }}</div>
                <div class="small text-secondary">admin</div>
              </div>
              <i class="bi bi-chevron-up kms-profile-chevron"></i>
            </summary>
            <div class="kms-profile-dropdown">
              <a href="{{ route('profile.edit') }}" class="kms-profile-item"><i class="bi bi-person"></i>Profile</a>
              <form method="POST" action="{{ route('logout') }}" class="m-0">
                @csrf
                <button type="submit" class="kms-profile-item kms-profile-logout"><i class="bi bi-box-arrow-right"></i>Logout</button>
              </form>
            </div>
          </details>
        </div>
      </aside>

      <div class="kms-content">
        <header class="kms-topbar border-bottom">
          <div class="d-flex align-items-center gap-3">
            <button type="button" class="btn btn-link p-0 border-0 text-secondary sidebar-toggle" aria-label="Toggle sidebar">
              <i class="bi bi-list fs-4"></i>
            </button>
            <h1 class="h4 mb-0">@yield('page_title', 'Dashboard')</h1>
          </div>
          <div class="d-flex align-items-center gap-2">
            <button type="button" class="btn btn-sm btn-outline-secondary theme-toggle"><i class="bi bi-moon"></i></button>
          </div>
        </header>
        <main class="kms-main">
          @yield('content')
        </main>
      </div>
    </div>
  @elseif(auth()->check() && auth()->user()->role !== 'admin' && !request()->routeIs('admin.*'))
    <div class="kms-shell kms-can-collapse">
      <aside class="kms-sidebar border-end">
        <div class="kms-brand">
          <div class="kms-brand-logo">K</div>
          <div class="kms-brand-copy">
            <div class="kms-brand-title">Kanmo KMS</div>
            <div class="kms-brand-sub">Knowledge Management</div>
          </div>
        </div>

        <nav class="kms-nav">
          <a href="{{ route('employee.dashboard') }}" class="kms-nav-link {{ request()->routeIs('employee.dashboard') || request()->routeIs('employee.sop.show') ? 'active' : '' }}">
            <i class="bi bi-grid"></i><span class="kms-nav-text">Dashboard</span>
          </a>
        </nav>

        <div class="kms-user">
          <details class="kms-profile-menu">
            <summary class="kms-profile-trigger">
              <div class="kms-user-avatar">{{ strtoupper(substr(auth()->user()->name, 0, 1)) }}</div>
              <div class="kms-user-meta">
                <div class="fw-semibold">{{ auth()->user()->name }}</div>
                <div class="small text-secondary">{{ auth()->user()->role }}</div>
              </div>
              <i class="bi bi-chevron-up kms-profile-chevron"></i>
            </summary>
            <div class="kms-profile-dropdown">
              <a href="{{ route('profile.edit') }}" class="kms-profile-item"><i class="bi bi-person"></i>Profile</a>
              <form method="POST" action="{{ route('logout') }}" class="m-0">
                @csrf
                <button type="submit" class="kms-profile-item kms-profile-logout"><i class="bi bi-box-arrow-right"></i>Logout</button>
              </form>
            </div>
          </details>
        </div>
      </aside>

      <div class="kms-content">
        <header class="kms-topbar border-bottom">
          <div class="d-flex align-items-center gap-3">
            <button type="button" class="btn btn-link p-0 border-0 text-secondary sidebar-toggle" aria-label="Toggle sidebar">
              <i class="bi bi-list fs-4"></i>
            </button>
            <h1 class="h4 mb-0">@yield('page_title', 'Employee')</h1>
          </div>
          <div class="d-flex align-items-center gap-2">
            <button type="button" class="btn btn-sm btn-outline-secondary theme-toggle"><i class="bi bi-moon"></i></button>
          </div>
        </header>
        <main class="kms-main">
          @yield('content')
        </main>
      </div>
    </div>
  @else
    <nav class="navbar navbar-expand-lg border-bottom bg-body">
      <div class="container py-2">
        <a class="navbar-brand fw-bold" href="/">Kanmo KMS</a>
        <div class="ms-auto d-flex align-items-center gap-2">
          <button type="button" class="btn btn-sm btn-outline-secondary theme-toggle">Theme</button>
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
  @endif

  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" crossorigin="anonymous"></script>
  <script>
    (function () {
      const saved = localStorage.getItem('kms_theme') || 'light';
      const html = document.documentElement;
      const applyTheme = (theme) => {
        html.setAttribute('data-theme', theme);
        document.body.classList.toggle('theme-dark', theme === 'dark');
        localStorage.setItem('kms_theme', theme);
      };
      applyTheme(saved);

      document.querySelectorAll('.theme-toggle').forEach(function (btn) {
        btn.addEventListener('click', function () {
          const next = html.getAttribute('data-theme') === 'dark' ? 'light' : 'dark';
          applyTheme(next);
        });
      });

      document.querySelectorAll('form[data-auto-submit]').forEach(function (form) {
        const textInputs = form.querySelectorAll('[data-auto-submit-input]');
        const selectInputs = form.querySelectorAll('[data-auto-submit-select]');
        let submitTimer;

        const submitForm = function () {
          if (typeof form.requestSubmit === 'function') {
            form.requestSubmit();
            return;
          }

          form.submit();
        };

        textInputs.forEach(function (input) {
          input.addEventListener('input', function () {
            clearTimeout(submitTimer);
            submitTimer = setTimeout(submitForm, 450);
          });
        });

        selectInputs.forEach(function (select) {
          select.addEventListener('change', function () {
            clearTimeout(submitTimer);
            submitTimer = setTimeout(submitForm, 200);
          });
        });
      });

      const shell = document.querySelector('.kms-shell.kms-can-collapse');
      const toggleBtn = document.querySelector('.sidebar-toggle');
      const sidebarStateKey = 'kms_sidebar_collapsed';
      const body = document.body;
      const isDensity80 = body.classList.contains('kms-density-80');
      const bodyZoomValue = parseFloat(window.getComputedStyle(body).zoom || '1');
      const hasZoomScale = Number.isFinite(bodyZoomValue) && bodyZoomValue < 0.99;
      const useCompactLayoutDensity = isDensity80 && !hasZoomScale;
      body.classList.toggle('kms-density-zoom', isDensity80 && hasZoomScale);
      body.classList.toggle('kms-density-layout', useCompactLayoutDensity);
      const expandedSidebarWidth = useCompactLayoutDensity ? '232px' : '290px';
      const collapsedSidebarWidth = useCompactLayoutDensity ? '72px' : '90px';
      const applySidebar = (collapsed) => {
        if (!shell) return;
        shell.classList.toggle('sidebar-collapsed', collapsed);
        shell.style.gridTemplateColumns = collapsed
          ? `${collapsedSidebarWidth} 1fr`
          : `${expandedSidebarWidth} 1fr`;
        localStorage.setItem(sidebarStateKey, collapsed ? '1' : '0');
      };

      if (shell) {
        const isCollapsed = localStorage.getItem(sidebarStateKey) === '1';
        applySidebar(isCollapsed);
      }

      if (toggleBtn && shell) {
        toggleBtn.addEventListener('click', function () {
          const next = !shell.classList.contains('sidebar-collapsed');
          applySidebar(next);
        });
      }
    })();
  </script>
</body>
</html>
