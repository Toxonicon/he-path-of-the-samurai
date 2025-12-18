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

  <div class="row g-4">
    {{-- левая колонка: JWST наблюдение --}}
    <div class="col-lg-7 fade-in">
      <div class="glass-card p-4 h-100">
        <div class="section-header">
          <span class="section-icon">🔭</span>
          <h5 class="section-title">JWST — Телескоп Джеймса Уэбба</h5>
        </div>
        <div class="text-muted">Данные от космического телескопа нового поколения</div>
      </div>
    </div>

    {{-- правая колонка: карта МКС --}}
    <div class="col-lg-5 fade-in-delay-1">
      <div class="glass-card p-4 h-100">
        <div class="section-header">
          <span class="section-icon">🛰️</span>
          <h5 class="section-title">МКС — Положение и движение</h5>
        </div>
        <div id="map" class="mb-3"></div>
        <div class="row g-2">
          <div class="col-6">
            <div class="chart-container">
              <canvas id="issSpeedChart" height="110"></canvas>
            </div>
          </div>
          <div class="col-6">
            <div class="chart-container">
              <canvas id="issAltChart" height="110"></canvas>
            </div>
          </div>
        </div>
      </div>
    </div>

    {{-- JWST Галерея --}}
    <div class="col-12 fade-in-delay-2">
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
              <span class="spinner" style="display:none"></span>
              ↻ Обновить
            </button>
          </div>
        </div>

          <!-- Skeleton loaders -->
          <div id="jwst-skeletons" class="jwst-gallery">
            @for($i = 0; $i < 12; $i++)
              <div class="skeleton skeleton-image"></div>
            @endfor
          </div>

          <!-- Галерея -->
          <div id="jwst-gallery" class="jwst-gallery"></div>

          <!-- Load More button -->
          <div class="text-center mt-3">
            <button class="btn btn-outline-secondary" onclick="jwstGallery.nextPage()">
              Загрузить ещё
            </button>
          </div>
            }
            .jwst-item{flex:0 0 180px; scroll-snap-align:start}
            .jwst-item img{width:100%; height:180px; object-fit:cover; border-radius:.5rem}
            .jwst-cap{font-size:.85rem; margin-top:.25rem}
            .jwst-nav{position:absolute; top:40%; transform:translateY(-50%); z-index:2}
            .jwst-prev{left:-.25rem} .jwst-next{right:-.25rem}
          </style>

          <div class="jwst-slider">
            <button class="btn btn-light border jwst-nav jwst-prev" type="button" aria-label="Prev">‹</button>
            <div id="jwstTrack" class="jwst-track border rounded"></div>
            <button class="btn btn-light border jwst-nav jwst-next" type="button" aria-label="Next">›</button>
          </div>

          <div id="jwstInfo" class="small text-muted mt-2"></div>
        </div>
      </div>
    </div>
  </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', async function () {
  // ====== карта и графики МКС (как раньше) ======
  if (typeof L !== 'undefined' && typeof Chart !== 'undefined') {
    const last = @json(($iss['payload'] ?? []));
    let lat0 = Number(last.latitude || 0), lon0 = Number(last.longitude || 0);
    const map = L.map('map', { attributionControl:false }).setView([lat0||0, lon0||0], lat0?3:2);
    L.tileLayer('https://{s}.tile.openstreetmap.de/{z}/{x}/{y}.png', { noWrap:true }).addTo(map);
    const trail  = L.polyline([], {weight:3}).addTo(map);
    const marker = L.marker([lat0||0, lon0||0]).addTo(map).bindPopup('МКС');

    const speedChart = new Chart(document.getElementById('issSpeedChart'), {
      type: 'line', data: { labels: [], datasets: [{ label: 'Скорость', data: [] }] },
      options: { responsive: true, scales: { x: { display: false } } }
    });
    const altChart = new Chart(document.getElementById('issAltChart'), {
      type: 'line', data: { labels: [], datasets: [{ label: 'Высота', data: [] }] },
      options: { responsive: true, scales: { x: { display: false } } }
    });

    async function loadTrend() {
      try {
        const r = await fetch('/api/iss/trend?limit=240');
        const js = await r.json();
        const pts = Array.isArray(js.points) ? js.points.map(p => [p.lat, p.lon]) : [];
        if (pts.length) {
          trail.setLatLngs(pts);
          marker.setLatLng(pts[pts.length-1]);
        }
        const t = (js.points||[]).map(p => new Date(p.at).toLocaleTimeString());
        speedChart.data.labels = t;
        speedChart.data.datasets[0].data = (js.points||[]).map(p => p.velocity);
        speedChart.update();
        altChart.data.labels = t;
        altChart.data.datasets[0].data = (js.points||[]).map(p => p.altitude);
        altChart.update();
      } catch(e) {}
    }
    loadTrend();
    setInterval(loadTrend, 15000);
  }

  // ====== JWST ГАЛЕРЕЯ ======
  const track = document.getElementById('jwstTrack');
  const info  = document.getElementById('jwstInfo');
  const form  = document.getElementById('jwstFilter');
  const srcSel = document.getElementById('srcSel');
  const sfxInp = document.getElementById('suffixInp');
  const progInp= document.getElementById('progInp');

  function toggleInputs(){
    sfxInp.style.display  = (srcSel.value==='suffix')  ? '' : 'none';
    progInp.style.display = (srcSel.value==='program') ? '' : 'none';
  }
  srcSel.addEventListener('change', toggleInputs); toggleInputs();

  async function loadFeed(qs){
    track.innerHTML = '<div class="p-3 text-muted">Загрузка…</div>';
    info.textContent= '';
    try{
      const url = '/api/jwst/feed?'+new URLSearchParams(qs).toString();
      const r = await fetch(url);
      const js = await r.json();
      track.innerHTML = '';
      (js.items||[]).forEach(it=>{
        const fig = document.createElement('figure');
        fig.className = 'jwst-item m-0';
        fig.innerHTML = `
          <a href="${it.link||it.url}" target="_blank" rel="noreferrer">
            <img loading="lazy" src="${it.url}" alt="JWST">
          </a>
          <figcaption class="jwst-cap">${(it.caption||'').replaceAll('<','&lt;')}</figcaption>`;
        track.appendChild(fig);
      });
      info.textContent = `Источник: ${js.source} · Показано ${js.count||0}`;
    }catch(e){
      track.innerHTML = '<div class="p-3 text-danger">Ошибка загрузки</div>';
    }
  }

  form.addEventListener('submit', function(ev){
    ev.preventDefault();
    const fd = new FormData(form);
    const q = Object.fromEntries(fd.entries());
    loadFeed(q);
  });

  // навигация
  document.querySelector('.jwst-prev').addEventListener('click', ()=> track.scrollBy({left:-600, behavior:'smooth'}));
  document.querySelector('.jwst-next').addEventListener('click', ()=> track.scrollBy({left: 600, behavior:'smooth'}));

  // стартовые данные
  loadFeed({source:'jpg', perPage:24});
});
</script>
@endsection
