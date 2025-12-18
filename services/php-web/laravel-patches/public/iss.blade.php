@extends('layouts.app')

@section('content')
<div class="container-fluid pb-5">
  {{-- Заголовок --}}
  <div class="row mb-4">
    <div class="col-12">
      <div class="glass-card p-4">
        <div class="section-header mb-2">
          <span class="section-icon">🛰️</span>
          <h3 class="section-title mb-0">Международная Космическая Станция</h3>
        </div>
        <p class="text-muted mb-0">Отслеживание в реальном времени</p>
      </div>
    </div>
  </div>

  {{-- Метрики МКС --}}
  <div class="row g-3 mb-4">
    <div class="col-6 col-md-3 fade-in">
      <div class="metric-card metric-velocity">
        <div class="metric-label">Скорость</div>
        <div class="metric-number" id="iss-velocity">
          {{ isset($last['payload']['velocity']) ? number_format($last['payload']['velocity'], 0, '', ' ') : '—' }}
        </div>
        <div class="metric-unit">км/ч</div>
      </div>
    </div>
    <div class="col-6 col-md-3 fade-in-delay-1">
      <div class="metric-card metric-altitude">
        <div class="metric-label">Высота</div>
        <div class="metric-number" id="iss-altitude">
          {{ isset($last['payload']['altitude']) ? number_format($last['payload']['altitude'], 0, '', ' ') : '—' }}
        </div>
        <div class="metric-unit">км</div>
      </div>
    </div>
    <div class="col-6 col-md-3 fade-in-delay-2">
      <div class="metric-card metric-coordinates">
        <div class="metric-label">Широта</div>
        <div class="metric-number" id="iss-latitude">
          {{ isset($last['payload']['latitude']) ? number_format($last['payload']['latitude'], 4) : '—' }}°
        </div>
      </div>
    </div>
    <div class="col-6 col-md-3 fade-in-delay-3">
      <div class="metric-card metric-coordinates">
        <div class="metric-label">Долгота</div>
        <div class="metric-number" id="iss-longitude">
          {{ isset($last['payload']['longitude']) ? number_format($last['payload']['longitude'], 4) : '—' }}°
        </div>
      </div>
    </div>
  </div>

  {{-- Карта и графики --}}
  <div class="row g-4 mb-4">
    <div class="col-lg-8 fade-in">
      <div class="glass-card p-4">
        <div class="section-header mb-3">
          <span class="section-icon">🗺️</span>
          <h5 class="section-title">Траектория полёта</h5>
        </div>
        <div id="issMap" style="height: 500px; border-radius: 12px; overflow: hidden;"></div>
        <div class="mt-3 small text-muted">
          Обновление каждые 10 секунд • Последнее: <span id="lastUpdate">только что</span>
        </div>
      </div>
    </div>

    <div class="col-lg-4 fade-in-delay-1">
      <div class="glass-card p-4 mb-3">
        <div class="section-header mb-3">
          <span class="section-icon">📊</span>
          <h5 class="section-title">Скорость (24ч)</h5>
        </div>
        <div class="chart-container" style="height: 200px;">
          <canvas id="issVelocityChart"></canvas>
        </div>
      </div>

      <div class="glass-card p-4">
        <div class="section-header mb-3">
          <span class="section-icon">📈</span>
          <h5 class="section-title">Высота (24ч)</h5>
        </div>
        <div class="chart-container" style="height: 200px;">
          <canvas id="issAltitudeChart"></canvas>
        </div>
      </div>
    </div>
  </div>

  {{-- Дополнительная информация --}}
  <div class="row g-4">
    <div class="col-md-4 fade-in-delay-2">
      <div class="glass-card p-4">
        <div class="section-header mb-3">
          <span class="section-icon">ℹ️</span>
          <h5 class="section-title">О станции</h5>
        </div>
        <ul class="list-unstyled">
          <li class="mb-2">🚀 <strong>Экипаж:</strong> 7 человек</li>
          <li class="mb-2">🔬 <strong>Модули:</strong> 16</li>
          <li class="mb-2">⚡ <strong>Солнечные панели:</strong> 8</li>
          <li class="mb-2">🌍 <strong>Орбитов в день:</strong> ~16</li>
          <li class="mb-2">⏱️ <strong>Период обращения:</strong> ~90 мин</li>
        </ul>
      </div>
    </div>

    <div class="col-md-4 fade-in-delay-3">
      <div class="glass-card p-4">
        <div class="section-header mb-3">
          <span class="section-icon">📡</span>
          <h5 class="section-title">API Endpoints</h5>
        </div>
        <div class="small">
          <div class="mb-2">
            <code class="bg-dark p-1 rounded">GET {{ $base }}/last</code>
            <p class="text-muted mb-0">Последние данные</p>
          </div>
          <div class="mb-2">
            <code class="bg-dark p-1 rounded">GET {{ $base }}/trend</code>
            <p class="text-muted mb-0">Тренд движения</p>
          </div>
          <div class="mb-2">
            <code class="bg-dark p-1 rounded">GET {{ $base }}/range</code>
            <p class="text-muted mb-0">Диапазон данных</p>
          </div>
        </div>
      </div>
    </div>

    <div class="col-md-4 fade-in-delay-4">
      <div class="glass-card p-4">
        <div class="section-header mb-3">
          <span class="section-icon">🎯</span>
          <h5 class="section-title">Статистика</h5>
        </div>
        <div id="issStats">
          <div class="mb-2">
            <strong>Смещение:</strong> <span id="stat-delta">—</span> км
          </div>
          <div class="mb-2">
            <strong>Интервал:</strong> <span id="stat-interval">—</span> сек
          </div>
          <div class="mb-2">
            <strong>Видимость:</strong> <span id="stat-visibility">—</span>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>

@push('scripts')
<script>
// ISS Live Tracking
const issTracking = {
  map: null,
  marker: null,
  trajectory: null,
  charts: {},
  
  async init() {
    await this.initMap();
    await this.initCharts();
    this.startUpdates();
  },
  
  async initMap() {
    const lat = {{ $last['payload']['latitude'] ?? 0 }};
    const lon = {{ $last['payload']['longitude'] ?? 0 }};
    
    this.map = L.map('issMap').setView([lat, lon], 3);
    
    L.tileLayer('https://{s}.basemaps.cartocdn.com/dark_all/{z}/{x}/{y}{r}.png', {
      attribution: '&copy; CARTO',
      maxZoom: 19
    }).addTo(this.map);
    
    const issIcon = L.divIcon({
      className: 'iss-marker',
      html: '<div style="font-size:36px; text-shadow: 0 0 15px #fff;">🛰️</div>',
      iconSize: [40, 40],
      iconAnchor: [20, 20]
    });
    
    this.marker = L.marker([lat, lon], { icon: issIcon }).addTo(this.map);
    
    this.trajectory = L.polyline([], {
      color: '#4facfe',
      weight: 3,
      opacity: 0.8,
      smoothFactor: 1
    }).addTo(this.map);
    
    await this.loadTrajectory();
  },
  
  async loadTrajectory() {
    try {
      const response = await fetch('/api/iss/trend?hours=3');
      const data = await response.json();
      
      if (data.data) {
        const points = data.data.map(item => [
          item.payload?.latitude || 0,
          item.payload?.longitude || 0
        ]).filter(([lat, lon]) => lat !== 0 && lon !== 0);
        
        this.trajectory.setLatLngs(points);
      }
    } catch (error) {
      console.error('Failed to load trajectory:', error);
    }
  },
  
  async initCharts() {
    const response = await fetch('/api/iss/trend?hours=24');
    const data = await response.json();
    const items = data.data || [];
    
    const labels = items.map(i => new Date(i.created_at).toLocaleTimeString('ru', { hour: '2-digit', minute: '2-digit' }));
    const velocities = items.map(i => i.payload?.velocity || 0);
    const altitudes = items.map(i => i.payload?.altitude || 0);
    
    // Velocity chart
    this.charts.velocity = new Chart(document.getElementById('issVelocityChart'), {
      type: 'line',
      data: {
        labels,
        datasets: [{
          data: velocities,
          borderColor: '#ff6b9d',
          backgroundColor: (context) => {
            const ctx = context.chart.ctx;
            const gradient = ctx.createLinearGradient(0, 0, 0, 200);
            gradient.addColorStop(0, 'rgba(255, 107, 157, 0.4)');
            gradient.addColorStop(1, 'rgba(255, 107, 157, 0.0)');
            return gradient;
          },
          tension: 0.4,
          fill: true,
          pointRadius: 0,
          borderWidth: 3
        }]
      },
      options: this.getChartOptions('км/ч')
    });
    
    // Altitude chart
    this.charts.altitude = new Chart(document.getElementById('issAltitudeChart'), {
      type: 'line',
      data: {
        labels,
        datasets: [{
          data: altitudes,
          borderColor: '#4facfe',
          backgroundColor: (context) => {
            const ctx = context.chart.ctx;
            const gradient = ctx.createLinearGradient(0, 0, 0, 200);
            gradient.addColorStop(0, 'rgba(79, 172, 254, 0.4)');
            gradient.addColorStop(1, 'rgba(79, 172, 254, 0.0)');
            return gradient;
          },
          tension: 0.4,
          fill: true,
          pointRadius: 0,
          borderWidth: 3
        }]
      },
      options: this.getChartOptions('км')
    });
  },
  
  getChartOptions(unit) {
    return {
      responsive: true,
      maintainAspectRatio: false,
      plugins: {
        legend: { display: false },
        tooltip: {
          backgroundColor: 'rgba(15, 12, 41, 0.95)',
          borderColor: '#667eea',
          borderWidth: 1,
          padding: 12,
          displayColors: false,
          callbacks: {
            label: (context) => `${context.parsed.y.toLocaleString()} ${unit}`
          }
        }
      },
      scales: {
        y: {
          grid: { color: 'rgba(255, 255, 255, 0.05)' },
          ticks: { color: 'rgba(255, 255, 255, 0.7)', font: { size: 10 } }
        },
        x: {
          grid: { display: false },
          ticks: { color: 'rgba(255, 255, 255, 0.7)', font: { size: 10 }, maxTicksLimit: 6 }
        }
      }
    };
  },
  
  async update() {
    try {
      const response = await fetch('/api/iss/last');
      const data = await response.json();
      const payload = data.payload || {};
      
      document.getElementById('iss-velocity').textContent = (payload.velocity || 0).toLocaleString();
      document.getElementById('iss-altitude').textContent = (payload.altitude || 0).toLocaleString();
      document.getElementById('iss-latitude').textContent = (payload.latitude || 0).toFixed(4) + '°';
      document.getElementById('iss-longitude').textContent = (payload.longitude || 0).toFixed(4) + '°';
      document.getElementById('lastUpdate').textContent = new Date().toLocaleTimeString('ru');
      
      if (this.marker && payload.latitude && payload.longitude) {
        const newPos = [payload.latitude, payload.longitude];
        this.marker.setLatLng(newPos);
        
        const currentPoints = this.trajectory.getLatLngs();
        currentPoints.push(newPos);
        if (currentPoints.length > 100) currentPoints.shift();
        this.trajectory.setLatLngs(currentPoints);
      }
      
      // Update stats
      const trendResp = await fetch('/api/iss/trend');
      const trend = await trendResp.json();
      if (trend.delta_km !== undefined) {
        document.getElementById('stat-delta').textContent = trend.delta_km.toFixed(2);
        document.getElementById('stat-interval').textContent = trend.dt_sec || '—';
        document.getElementById('stat-visibility').textContent = payload.visibility || 'unknown';
      }
    } catch (error) {
      console.error('Update failed:', error);
    }
  },
  
  startUpdates() {
    setInterval(() => this.update(), 10000);
  }
};

document.addEventListener('DOMContentLoaded', () => {
  issTracking.init();
});
</script>
@endpush
@endsection
