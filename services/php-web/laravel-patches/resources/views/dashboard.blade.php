@extends('layouts.app')

@section('content')
<div class="container pb-5">
  {{-- верхние карточки метрик --}}
  <div class="row g-3 mb-4">
    <div class="col-6 col-md-3 fade-in">
      <div class="metric-card metric-velocity">
        <div class="metric-label">Скорость МКС</div>
        <div class="metric-number metric-value" data-metric="velocity" data-value="{{ $metrics['velocity'] ?? 0 }}">
          {{ isset($metrics['velocity']) ? number_format($metrics['velocity'], 0, '', ' ') : '—' }}
        </div>
        <div class="metric-unit">км/ч</div>
      </div>
    </div>
    <div class="col-6 col-md-3 fade-in-delay-1">
      <div class="metric-card metric-altitude">
        <div class="metric-label">Высота МКС</div>
        <div class="metric-number metric-value" data-metric="altitude" data-value="{{ $metrics['altitude'] ?? 0 }}">
          {{ isset($metrics['altitude']) ? number_format($metrics['altitude'], 0, '', ' ') : '—' }}
        </div>
        <div class="metric-unit">км</div>
      </div>
    </div>
    <div class="col-6 col-md-3 fade-in-delay-2">
      <div class="metric-card metric-coordinates">
        <div class="metric-label">Широта</div>
        <div class="metric-number metric-value" data-metric="latitude">
          {{ isset($metrics['latitude']) ? number_format($metrics['latitude'], 2) : '—' }}°
        </div>
      </div>
    </div>
    <div class="col-6 col-md-3 fade-in-delay-3">
      <div class="metric-card metric-coordinates">
        <div class="metric-label">Долгота</div>
        <div class="metric-number metric-value" data-metric="longitude">
          {{ isset($metrics['longitude']) ? number_format($metrics['longitude'], 2) : '—' }}°
        </div>
      </div>
    </div>
  </div>

  <div class="row g-4 mb-4">
    {{-- карта МКС на всю ширину --}}
    <div class="col-12 fade-in">
      <div class="glass-card p-4">
        <div class="section-header mb-3">
          <span class="section-icon">�️</span>
          <h5 class="section-title">МКС — Положение и движение</h5>
        </div>
        <div id="map" class="mb-3" style="height: 450px;"></div>
        <div class="row g-2">
          <div class="col-md-6">
            <div class="chart-container" style="height: 180px;">
              <canvas id="issSpeedChart"></canvas>
            </div>
          </div>
          <div class="col-md-6">
            <div class="chart-container" style="height: 180px;">
              <canvas id="issAltChart"></canvas>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>

  <div class="row g-4">
    {{-- JWST данные --}}
    <div class="col-lg-5 fade-in-delay-1">
      <div class="glass-card p-4 h-100">
        <div class="section-header mb-3">
          <span class="section-icon">�</span>
          <h5 class="section-title">JWST — Телескоп Джеймса Уэбба</h5>
        </div>
        <div class="mb-3">
          <h6 class="text-muted mb-2">Текущие наблюдения</h6>
          <div id="jwstCurrentObservation" class="small">
            <div class="mb-2">
              <strong>Цель:</strong> <span id="jwst-target">Загрузка...</span>
            </div>
            <div class="mb-2">
              <strong>Инструмент:</strong> <span id="jwst-instrument">—</span>
            </div>
            <div class="mb-2">
              <strong>Категория:</strong> <span id="jwst-category">—</span>
            </div>
          </div>
        </div>
        <div>
          <h6 class="text-muted mb-2">О телескопе</h6>
          <ul class="list-unstyled small">
            <li class="mb-2">🌌 <strong>Запуск:</strong> 25 декабря 2021</li>
            <li class="mb-2">🔬 <strong>Диаметр зеркала:</strong> 6.5 метров</li>
            <li class="mb-2">🌡️ <strong>Температура:</strong> -233°C</li>
            <li class="mb-2">📡 <strong>Орбита:</strong> Точка Лагранжа L2</li>
            <li class="mb-2">🎯 <strong>Расстояние:</strong> 1.5 млн км от Земли</li>
          </ul>
        </div>
      </div>
    </div>

    {{-- JWST Галерея --}}
    <div class="col-lg-7 fade-in-delay-2">
      <div class="glass-card p-4">
        <div class="d-flex justify-content-between align-items-center mb-4">
          <div class="section-header mb-0">
            <span class="section-icon">🌌</span>
            <h5 class="section-title">JWST — Галерея космоса</h5>
          </div>
          <div class="d-flex gap-2 flex-wrap">
            <select class="form-select form-select-sm" id="instrumentFilter" style="width:150px">
              <option value="">Все инструменты</option>
              <option>NIRCam</option>
              <option>MIRI</option>
              <option>NIRISS</option>
              <option>NIRSpec</option>
              <option>FGS</option>
            </select>
            <button class="btn btn-sm btn-primary" onclick="jwstGallery.load()">
              <span class="spinner" id="jwstSpinner" style="display:none"></span>
              ↻ Обновить
            </button>
          </div>
        </div>
        
        <div id="jwstGallery" class="row g-3">
          <div class="col-12 text-center text-muted py-5">
            <div class="spinner-lg mx-auto mb-3"></div>
            <p>Загрузка изображений...</p>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>

<script>
// JWST Gallery
const jwstGallery = {
  images: [],
  
  async load() {
    const spinner = document.getElementById('jwstSpinner');
    const gallery = document.getElementById('jwstGallery');
    const filter = document.getElementById('instrumentFilter').value;
    
    if (spinner) spinner.style.display = 'inline-block';
    gallery.innerHTML = '<div class="col-12 text-center py-4"><div class="spinner-lg mx-auto"></div></div>';
    
    try {
      const response = await fetch('/api/jwst/feed');
      const data = await response.json();
      
      this.images = data.items || [];
      
      if (this.images.length === 0) {
        gallery.innerHTML = '<div class="col-12 text-center text-muted py-4">Нет изображений</div>';
        return;
      }
      
      // Обновляем текущие наблюдения
      const firstImage = this.images[0];
      if (firstImage) {
        document.getElementById('jwst-target').textContent = firstImage.title || 'Неизвестно';
        document.getElementById('jwst-instrument').textContent = firstImage.instrument || '—';
        document.getElementById('jwst-category').textContent = firstImage.category || '—';
      }
      
      // Фильтруем по инструменту
      let filtered = this.images;
      if (filter) {
        filtered = this.images.filter(img => img.instrument === filter);
      }
      
      // Ограничиваем до 12 изображений
      filtered = filtered.slice(0, 12);
      
      // Рендерим галерею
      gallery.innerHTML = filtered.map(img => `
        <div class="col-md-4 col-lg-3">
          <div class="jwst-item">
            <img src="${img.thumbnail || img.url}" 
                 alt="${img.title || 'JWST Image'}" 
                 class="img-fluid rounded"
                 loading="lazy"
                 onerror="this.src='data:image/svg+xml,%3Csvg xmlns=%22http://www.w3.org/2000/svg%22 width=%22300%22 height=%22300%22%3E%3Crect fill=%22%23667eea%22 width=%22300%22 height=%22300%22/%3E%3Ctext fill=%22white%22 x=%2250%25%22 y=%2250%25%22 text-anchor=%22middle%22 dy=%22.3em%22 font-size=%2218%22%3ENo Image%3C/text%3E%3C/svg%3E'">
            <div class="jwst-caption">
              <div class="small fw-bold">${img.title || 'Untitled'}</div>
              <div class="small text-muted">${img.instrument || '—'}</div>
            </div>
          </div>
        </div>
      `).join('');
      
    } catch (error) {
      console.error('JWST load error:', error);
      gallery.innerHTML = '<div class="col-12 text-center text-danger py-4">⚠️ Ошибка загрузки изображений</div>';
    } finally {
      if (spinner) spinner.style.display = 'none';
    }
  }
};

// Загрузка при старте
document.addEventListener('DOMContentLoaded', () => {
  jwstGallery.load();
  
  // Обработчик фильтра
  document.getElementById('instrumentFilter')?.addEventListener('change', () => {
    jwstGallery.load();
  });
});
</script>
@endsection
