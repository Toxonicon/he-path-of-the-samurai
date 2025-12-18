<!doctype html>
<html lang="ru">
<head>
 <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script src="/assets/ui.js"></script>
<script src="/assets/charts.js"></script>

<!-- Генерация звёзд -->
<script>
  const starsContainer = document.getElementById('stars');
  for (let i = 0; i < 100; i++) {
    const star = document.createElement('div');
    star.className = 'star';
    star.style.left = Math.random() * 100 + '%';
    star.style.top = Math.random() * 100 + '%';
    star.style.animationDelay = Math.random() * 3 + 's';
    starsContainer.appendChild(star);
  }
</script>
</body>
</html>charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Cassiopeia - Space Monitoring</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css"/>
  <style>
    #map { height: 340px; }
    @import url('/assets/animations.css');
    @import url('/assets/theme.css');
  </style>
</head>
<body>
<!-- Звёздное небо -->
<div class="stars" id="stars"></div>

<nav class="navbar navbar-expand-lg mb-4">
  <div class="container">
    <a class="navbar-brand" href="/dashboard">🌌 Cassiopeia</a>
    <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
      <span class="navbar-toggler-icon"></span>
    </button>
    <div class="collapse navbar-collapse" id="navbarNav">
      <div class="navbar-nav ms-auto">
        <a class="nav-link" href="/dashboard">Dashboard</a>
        <a class="nav-link" href="/iss">ISS Tracking</a>
        <a class="nav-link" href="/osdr">OSDR Data</a>
      </div>
    </div>
  </div>
</nav>

<!-- Loading Overlay -->
<div class="loading-overlay">
  <div class="spinner-lg"></div>
</div>

<!-- Toast Container -->
<div class="toast-container position-fixed top-0 end-0 p-3"></div>

@yield('content')

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script src="/assets/ui.js"></script>
<script src="/assets/charts.js"></script>
</body>
</html>
