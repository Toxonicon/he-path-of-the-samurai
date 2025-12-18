<!doctype html>
<html lang="ru">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Cassiopeia - Space Monitoring</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css"/>
  <link rel="stylesheet" href="/assets/animations.css">
  <link rel="stylesheet" href="/assets/theme.css">
  <style>
    #map { 
      height: 340px; 
      border-radius: 12px;
      overflow: hidden;
    }
    .chart-container { 
      position: relative; 
      height: 110px; 
    }
  </style>
</head>
<body>
  <!-- Звёздный фон -->
  <div class="stars" id="stars"></div>

  <!-- Навбар -->
  <nav class="navbar navbar-expand-lg navbar-dark">
    <div class="container-fluid">
      <a class="navbar-brand d-flex align-items-center" href="/">
        <span style="font-size:1.5rem; margin-right:8px">🌌</span>
        <span class="fw-bold">Cassiopeia</span>
      </a>
      <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
        <span class="navbar-toggler-icon"></span>
      </button>
      <div class="collapse navbar-collapse" id="navbarNav">
        <ul class="navbar-nav ms-auto">
          <li class="nav-item">
            <a class="nav-link {{ Request::is('/') ? 'active' : '' }}" href="/">Dashboard</a>
          </li>
          <li class="nav-item">
            <a class="nav-link {{ Request::is('iss*') ? 'active' : '' }}" href="/iss">ISS Tracking</a>
          </li>
          <li class="nav-item">
            <a class="nav-link {{ Request::is('osdr*') ? 'active' : '' }}" href="/osdr">OSDR Data</a>
          </li>
        </ul>
      </div>
    </div>
  </nav>

  <!-- Loading Overlay -->
  <div class="loading-overlay">
    <div class="spinner-lg"></div>
  </div>

  <!-- Toast Container -->
  <div class="toast-container position-fixed top-0 end-0 p-3"></div>

  <!-- Контент -->
  <div class="container-fluid py-4">
    @yield('content')
  </div>

  <!-- Скрипты -->
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
  <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
  <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
  <script src="/assets/ui.js"></script>
  <script src="/assets/charts.js"></script>

  <!-- Генерация звёзд -->
  <script>
    document.addEventListener('DOMContentLoaded', function() {
      const starsContainer = document.getElementById('stars');
      if (starsContainer) {
        for (let i = 0; i < 100; i++) {
          const star = document.createElement('div');
          star.className = 'star';
          star.style.left = Math.random() * 100 + '%';
          star.style.top = Math.random() * 100 + '%';
          star.style.animationDelay = Math.random() * 3 + 's';
          starsContainer.appendChild(star);
        }
      }
    });
  </script>

  @stack('scripts')
</body>
</html>
