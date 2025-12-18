<!doctype html>
<html lang="ru">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Cassiopeia - Space Monitoring</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css"/>
  <style>
    #map { height: 340px; }
    @import url('/assets/animations.css');
  </style>
</head>
<body>
<nav class="navbar navbar-expand-lg bg-body-tertiary mb-3 shadow-sm">
  <div class="container">
    <a class="navbar-brand fw-bold" href="/dashboard">🌌 Cassiopeia</a>
    <div class="navbar-nav ms-auto">
      <a class="nav-link" href="/dashboard">Dashboard</a>
      <a class="nav-link" href="/iss">ISS Track</a>
      <a class="nav-link" href="/osdr">OSDR Data</a>
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
</body>
</html>
