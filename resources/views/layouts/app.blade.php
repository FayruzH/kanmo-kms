<!doctype html>
<html lang="en" data-theme="light">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Kanmo KMS</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
  @vite(['resources/css/app.css', 'resources/js/app.js'])
  @stack('styles')
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

    .kms-mobile-menu-head {
      display: none;
    }

    @media (max-width: 992px) {
      body.kms-mobile-nav-open {
        overflow: hidden !important;
      }

      .kms-shell.kms-can-collapse {
        display: block !important;
        grid-template-columns: 1fr !important;
      }

      .kms-shell.kms-can-collapse::before {
        content: "";
        position: fixed;
        inset: 0;
        z-index: 1025;
        background: rgba(8, 16, 30, 0.42);
        opacity: 0;
        pointer-events: none;
        transition: opacity 0.2s ease;
      }

      .kms-shell.kms-can-collapse.mobile-nav-open::before,
      body.kms-mobile-nav-open .kms-shell.kms-can-collapse::before {
        opacity: 1;
        pointer-events: auto;
      }

      .kms-shell.kms-can-collapse > .kms-sidebar {
        position: fixed !important;
        top: 0 !important;
        left: 0 !important;
        z-index: 1035 !important;
        width: min(86vw, 340px) !important;
        height: 100svh !important;
        max-height: 100svh !important;
        overflow-y: auto !important;
        border-right: 1px solid var(--kms-sidebar-border) !important;
        border-bottom: 0 !important;
        box-shadow: 24px 0 48px rgba(15, 23, 42, 0.18) !important;
        transform: translateX(-110%) !important;
        transition: transform 0.24s ease !important;
      }

      .kms-shell.kms-can-collapse.mobile-nav-open > .kms-sidebar,
      body.kms-mobile-nav-open .kms-shell.kms-can-collapse > .kms-sidebar {
        transform: translateX(0) !important;
      }

      .kms-mobile-menu-head {
        display: flex !important;
        align-items: center;
        justify-content: space-between;
        gap: 12px;
        padding: 16px 18px;
        border-bottom: 1px solid var(--kms-sidebar-border);
        background: rgba(255, 255, 255, 0.46);
        color: #2d1d16;
        font-size: 1.15rem;
        font-weight: 700;
      }

      .kms-mobile-menu-close {
        width: 36px;
        height: 36px;
        border: 0;
        border-radius: 999px;
        display: inline-grid;
        place-items: center;
        background: transparent;
        color: #6c5347;
      }

      .kms-shell.kms-can-collapse > .kms-content {
        display: block !important;
        width: 100% !important;
        min-width: 0 !important;
      }

      .kms-shell.kms-can-collapse .sidebar-toggle {
        display: inline-flex !important;
        align-items: center;
        justify-content: center;
      }
    }
  </style>
</head>



<body class="{{ request()->routeIs('admin.*') || request()->routeIs('employee.*') || request()->routeIs('public.*') || request()->routeIs('dashboard') ? 'kms-density-80' : '' }}">
  @if(request()->routeIs('admin.*'))
    <div class="kms-shell kms-can-collapse">
      <aside class="kms-sidebar border-end">
        <div class="kms-mobile-menu-head">
          <span>Menu</span>
          <button type="button" class="kms-mobile-menu-close" data-mobile-menu-close aria-label="Close menu">
            <i class="bi bi-x-lg"></i>
          </button>
        </div>

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
          @if(\Illuminate\Support\Facades\Route::has('admin.feedback.index'))
            <a href="{{ route('admin.feedback.index') }}" class="kms-nav-link {{ request()->routeIs('admin.feedback.*') ? 'active' : '' }}">
              <i class="bi bi-chat-left-text"></i><span class="kms-nav-text">Feedback</span>
            </a>
          @endif
          <a href="{{ route('admin.settings.index') }}" class="kms-nav-link {{ request()->routeIs('admin.settings.*') ? 'active' : '' }}">
            <i class="bi bi-gear"></i><span class="kms-nav-text">Settings</span>
          </a>
        </nav>

        <div class="kms-user">
          @auth
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
          @else
            <div class="kms-profile-trigger">
              <div class="kms-user-avatar">A</div>
              <div class="kms-user-meta">
                <div class="fw-semibold">Admin Area</div>
                <div class="small text-secondary">Public Access Enabled</div>
              </div>
            </div>
          @endauth
        </div>
      </aside>

      <div class="kms-content">
        <header class="kms-topbar border-bottom">
          <div class="d-flex align-items-center gap-3">
            <button type="button" class="btn btn-link p-0 border-0 text-secondary sidebar-toggle" data-sidebar-toggle aria-label="Toggle sidebar" aria-expanded="false">
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
  @elseif(!request()->routeIs('admin.*'))
    <div class="kms-shell kms-can-collapse">
      <aside class="kms-sidebar border-end">
        <div class="kms-mobile-menu-head">
          <span>Menu</span>
          <button type="button" class="kms-mobile-menu-close" data-mobile-menu-close aria-label="Close menu">
            <i class="bi bi-x-lg"></i>
          </button>
        </div>

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
          <a href="{{ route('employee.chatbot') }}" class="kms-nav-link {{ request()->routeIs('employee.chatbot') ? 'active' : '' }}">
            <i class="bi bi-robot"></i><span class="kms-nav-text">Chatbot</span>
          </a>
          @if(\Illuminate\Support\Facades\Route::has('employee.feedback.create'))
            <a href="{{ route('employee.feedback.create') }}" class="kms-nav-link {{ request()->routeIs('employee.feedback.*') ? 'active' : '' }}">
              <i class="bi bi-chat-left-dots"></i><span class="kms-nav-text">Feedback</span>
            </a>
          @endif
        </nav>

        <div class="kms-user">
          @auth
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
          @else
            <div class="kms-profile-trigger">
              <div class="kms-user-avatar">G</div>
              <div class="kms-user-meta">
                <div class="fw-semibold">Guest User</div>
                <div class="small text-secondary">Public Access Enabled</div>
              </div>
            </div>
          @endauth
        </div>
      </aside>

      <div class="kms-content">
        <header class="kms-topbar border-bottom">
          <div class="d-flex align-items-center gap-3">
            <button type="button" class="btn btn-link p-0 border-0 text-secondary sidebar-toggle" data-sidebar-toggle aria-label="Toggle sidebar" aria-expanded="false">
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
            <span class="small opacity-75">Public Access Enabled</span>
          @endauth
        </div>
      </div>
    </nav>
    <main class="py-4">
      @yield('content')
    </main>
  @endif

  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" crossorigin="anonymous"></script>
  @stack('scripts')
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
      const toggleButtons = Array.from(document.querySelectorAll('[data-sidebar-toggle]'));
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
      const desktopSidebarQuery = window.matchMedia('(min-width: 993px)');

      const setMobileNavOpen = (open) => {
        if (!shell) return;
        shell.classList.toggle('mobile-nav-open', open);
        body.classList.toggle('kms-mobile-nav-open', open);
        toggleButtons.forEach(function (button) {
          button.setAttribute('aria-expanded', open ? 'true' : 'false');
        });
      };

      const applySidebar = (collapsed) => {
        if (!shell) return;

        if (!desktopSidebarQuery.matches) {
          shell.classList.remove('sidebar-collapsed');
          shell.style.gridTemplateColumns = '';
          toggleButtons.forEach(function (button) {
            button.setAttribute('aria-expanded', shell.classList.contains('mobile-nav-open') ? 'true' : 'false');
          });
          return;
        }

        setMobileNavOpen(false);
        shell.classList.toggle('sidebar-collapsed', collapsed);
        shell.style.gridTemplateColumns = collapsed
          ? `${collapsedSidebarWidth} 1fr`
          : `${expandedSidebarWidth} 1fr`;
        localStorage.setItem(sidebarStateKey, collapsed ? '1' : '0');
      };

      const syncSidebarForViewport = () => {
        if (!shell) return;
        applySidebar(localStorage.getItem(sidebarStateKey) === '1');
      };

      if (shell) {
        syncSidebarForViewport();
      }

      document.addEventListener('click', function (event) {
        const toggle = event.target.closest('[data-sidebar-toggle]');
        if (toggle && shell) {
          event.preventDefault();

          if (!desktopSidebarQuery.matches) {
            setMobileNavOpen(!shell.classList.contains('mobile-nav-open'));
            return;
          }

          const next = !shell.classList.contains('sidebar-collapsed');
          applySidebar(next);
          return;
        }

        if (event.target.closest('[data-mobile-menu-close]')) {
          setMobileNavOpen(false);
          return;
        }

        if (!shell || desktopSidebarQuery.matches || !shell.classList.contains('mobile-nav-open')) {
          return;
        }

        const clickedOutsideMenu = !event.target.closest('.kms-sidebar');
        const clickedNavLink = Boolean(event.target.closest('.kms-sidebar .kms-nav-link'));

        if (clickedOutsideMenu || clickedNavLink) {
          setMobileNavOpen(false);
        }
      });

      document.addEventListener('keydown', function (event) {
        if (event.key === 'Escape') {
          setMobileNavOpen(false);
        }
      });

      if (typeof desktopSidebarQuery.addEventListener === 'function') {
        desktopSidebarQuery.addEventListener('change', syncSidebarForViewport);
      } else if (typeof desktopSidebarQuery.addListener === 'function') {
        desktopSidebarQuery.addListener(syncSidebarForViewport);
      }
    })();
  </script>
</body>
</html>
