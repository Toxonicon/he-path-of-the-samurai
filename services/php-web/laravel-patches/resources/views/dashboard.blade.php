@extends('layouts.app')

@section('content')
<div class="container pb-5">
  {{-- верхние карточки метрик --}}
  <div class="row g-3 mb-3">
    <div class="col-6 col-md-3 fade-in">
      <div class="card card-animated shadow-sm h-100">
        <div class="card-body text-center">
          <div class="small text-muted mb-1">Скорость МКС</div>
          <div class="fs-3 fw-bold metric-value" data-value="{{ $metrics['velocity'] ?? 0 }}">
            {{ isset($metrics['velocity']) ? number_format($metrics['velocity'], 0, '', ' ') : '—' }}
          </div>
          <div class="small text-muted">км/ч</div>
        </div>
      </div>
    </div>
    <div class="col-6 col-md-3 fade-in-delay-1">
      <div class="card card-animated shadow-sm h-100">
        <div class="card-body text-center">
          <div class="small text-muted mb-1">Высота МКС</div>
          <div class="fs-3 fw-bold metric-value" data-value="{{ $metrics['altitude'] ?? 0 }}">
            {{ isset($metrics['altitude']) ? number_format($metrics['altitude'], 0, '', ' ') : '—' }}
          </div>
          <div class="small text-muted">км</div>
        </div>
      </div>
    </div>
    <div class="col-6 col-md-3 fade-in-delay-2">
      <div class="card card-animated shadow-sm h-100">
        <div class="card-body text-center">
          <div class="small text-muted mb-1">Широта</div>
          <div class="fs-4 fw-bold">{{ isset($metrics['latitude']) ? number_format($metrics['latitude'], 2) : '—' }}°</div>
        </div>
      </div>
    </div>
    <div class="col-6 col-md-3 fade-in-delay-3">
      <div class="card card-animated shadow-sm h-100">
        <div class="card-body text-center">
          <div class="small text-muted mb-1">Долгота</div>
          <div class="fs-4 fw-bold">{{ isset($metrics['longitude']) ? number_format($metrics['longitude'], 2) : '—' }}°</div>
        </div>
      </div>
    </div>
  </div>

  <div class="row g-3">
    {{-- левая колонка: JWST наблюдение --}}
    <div class="col-lg-7 fade-in">
      <div class="card shadow-sm h-100">
        <div class="card-body">
          <h5 class="card-title">🔭 JWST — Текущее наблюдение</h5>
          <div class="text-muted">Данные телескопа James Webb Space Telescope</div>
        </div>
      </div>
    </div>

    {{-- правая колонка: карта МКС --}}
    <div class="col-lg-5 fade-in-delay-1">
      <div class="card shadow-sm h-100">
        <div class="card-body">
          <h5 class="card-title">🛰️ МКС — Положение и движение</h5>
          <div id="map" class="rounded mb-2 border" style="height:300px"></div>
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
    </div>

    {{-- JWST Галерея --}}
    <div class="col-12 fade-in-delay-2">
      <div class="card shadow-sm">
        <div class="card-body">
          <div class="d-flex justify-content-between align-items-center mb-3">
            <h5 class="card-title m-0">🌌 JWST — Галерея изображений</h5>
            <div class="d-flex gap-2 flex-wrap">
              <select class="form-select form-select-sm" id="instrumentFilter" style="width:140px">
                <option value="">Все инструменты</option>
                <option>NIRCam</option>
                <option>MIRI</option>
                <option>NIRISS</option>
                <option>NIRSpec</option>
                <option>FGS</option>
              </select>
              <button class="btn btn-sm btn-outline-primary" onclick="jwstGallery.load()">
                <span class="spinner" style="display:none"></span>
                Обновить
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

    <!-- ASTRO — события -->
    <div class="col-12 order-first mt-3">
      <div class="card shadow-sm">
        <div class="card-body">
          <div class="d-flex justify-content-between align-items-center mb-2">
            <h5 class="card-title m-0">Астрономические события (AstronomyAPI)</h5>
            <form id="astroForm" class="row g-2 align-items-center">
              <div class="col-auto">
                <input type="number" step="0.0001" class="form-control form-control-sm" name="lat" value="55.7558" placeholder="lat">
              </div>
              <div class="col-auto">
                <input type="number" step="0.0001" class="form-control form-control-sm" name="lon" value="37.6176" placeholder="lon">
              </div>
              <div class="col-auto">
                <input type="number" min="1" max="30" class="form-control form-control-sm" name="days" value="7" style="width:90px" title="дней">
              </div>
              <div class="col-auto">
                <button class="btn btn-sm btn-primary" type="submit">Показать</button>
              </div>
            </form>
          </div>

          <div class="table-responsive">
            <table class="table table-sm align-middle">
              <thead>
                <tr><th>#</th><th>Тело</th><th>Событие</th><th>Когда (UTC)</th><th>Дополнительно</th></tr>
              </thead>
              <tbody id="astroBody">
                <tr><td colspan="5" class="text-muted">нет данных</td></tr>
              </tbody>
            </table>
          </div>

          <details class="mt-2">
            <summary>Полный JSON</summary>
            <pre id="astroRaw" class="bg-light rounded p-2 small m-0" style="white-space:pre-wrap"></pre>
          </details>
        </div>
      </div>
    </div>

    <script>
      document.addEventListener('DOMContentLoaded', () => {
        const form = document.getElementById('astroForm');
        const body = document.getElementById('astroBody');
        const raw  = document.getElementById('astroRaw');

        function normalize(node){
          const name = node.name || node.body || node.object || node.target || '';
          const type = node.type || node.event_type || node.category || node.kind || '';
          const when = node.time || node.date || node.occursAt || node.peak || node.instant || '';
          const extra = node.magnitude || node.mag || node.altitude || node.note || '';
          return {name, type, when, extra};
        }

        function collect(root){
          const rows = [];
          (function dfs(x){
            if (!x || typeof x !== 'object') return;
            if (Array.isArray(x)) { x.forEach(dfs); return; }
            if ((x.type || x.event_type || x.category) && (x.name || x.body || x.object || x.target)) {
              rows.push(normalize(x));
            }
            Object.values(x).forEach(dfs);
          })(root);
          return rows;
        }

        async function load(q){
          body.innerHTML = '<tr><td colspan="5" class="text-muted">Загрузка…</td></tr>';
          const url = '/api/astro/events?' + new URLSearchParams(q).toString();
          try{
            const r  = await fetch(url);
            const js = await r.json();
            raw.textContent = JSON.stringify(js, null, 2);

            const rows = collect(js);
            if (!rows.length) {
              body.innerHTML = '<tr><td colspan="5" class="text-muted">события не найдены</td></tr>';
              return;
            }
            body.innerHTML = rows.slice(0,200).map((r,i)=>`
              <tr>
                <td>${i+1}</td>
                <td>${r.name || '—'}</td>
                <td>${r.type || '—'}</td>
                <td><code>${r.when || '—'}</code></td>
                <td>${r.extra || ''}</td>
              </tr>
            `).join('');
          }catch(e){
            body.innerHTML = '<tr><td colspan="5" class="text-danger">ошибка загрузки</td></tr>';
          }
        }

        form.addEventListener('submit', ev=>{
          ev.preventDefault();
          const q = Object.fromEntries(new FormData(form).entries());
          load(q);
        });

        // автозагрузка
        load({lat: form.lat.value, lon: form.lon.value, days: form.days.value});
      });
    </script>


{{-- ===== Данный блок ===== --}}
<div class="card mt-3">
  <div class="card-header fw-semibold">CMS</div>
  <div class="card-body">
    @php
      try {
        // «плохо»: запрос из Blade, без кэша, без репозитория
        $___b = DB::selectOne("SELECT content FROM cms_blocks WHERE slug='dashboard_experiment' AND is_active = TRUE LIMIT 1");
        echo $___b ? $___b->content : '<div class="text-muted">блок не найден</div>';
      } catch (\Throwable $e) {
        echo '<div class="text-danger">ошибка БД: '.e($e->getMessage()).'</div>';
      }
    @endphp
  </div>
</div>

{{-- ===== CMS-блок из БД (нарочно сырая вставка) ===== --}}
<div class="card mt-3">
  <div class="card-header fw-semibold">CMS — блок из БД</div>
  <div class="card-body">
    @php
      try {
        // «плохо»: запрос из Blade, без кэша, без репозитория
        $___b = DB::selectOne("SELECT content FROM cms_blocks WHERE slug='dashboard_experiment' AND is_active = TRUE LIMIT 1");
        echo $___b ? $___b->content : '<div class="text-muted">блок не найден</div>';
      } catch (\Throwable $e) {
        echo '<div class="text-danger">ошибка БД: '.e($e->getMessage()).'</div>';
      }
    @endphp
  </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', () => {
  if (window.L && window._issMapTileLayer) {
    const map  = window._issMap;
    let   tl   = window._issMapTileLayer;
    tl.on('tileerror', () => {
      try {
        map.removeLayer(tl);
      } catch(e) {}
      tl = L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {attribution: ''});
      tl.addTo(map);
      window._issMapTileLayer = tl;
    });
  }
});
</script>

{{-- ===== CMS-блок из БД (нарочно сырая вставка) ===== --}}
<div class="card mt-3">
  <div class="card-header fw-semibold">CMS — блок из БД</div>
  <div class="card-body">
    @php
      try {
        // «плохо»: запрос из Blade, без кэша, без репозитория
        $___b = DB::selectOne("SELECT content FROM cms_blocks WHERE slug='dashboard_experiment' AND is_active = TRUE LIMIT 1");
        echo $___b ? $___b->content : '<div class="text-muted">блок не найден</div>';
      } catch (\Throwable $e) {
        echo '<div class="text-danger">ошибка БД: '.e($e->getMessage()).'</div>';
      }
    @endphp
  </div>
</div>
