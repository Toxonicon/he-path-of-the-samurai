@extends('layouts.app')

@section('content')
<div class="container-fluid page-transition">
  <!-- Контролы таблицы -->
  <div id="tableControls"></div>

  <div class="row mb-4">
    <div class="col-12">
      <div class="glass-card p-4 hover-lift">
        <div class="section-header mb-3">
          <span class="section-icon float">🌠</span>
          <h3 class="section-title mb-0 glow">Астрономические события (AstronomyAPI)</h3>
        </div>
        <p class="text-muted mb-4">События из AstronomyAPI в реальном времени</p>

        <div class="d-flex gap-3 mb-4 flex-wrap stagger-item">
          <div class="metric-card metric-velocity flex-grow-1 bounce-in">
            <div class="metric-label">Всего событий</div>
            <div class="metric-number counter-up" id="totalEvents">—</div>
          </div>
          <div class="metric-card metric-altitude flex-grow-1 bounce-in animation-delay-1">
            <div class="metric-label">Уникальные тела</div>
            <div class="metric-number counter-up" id="uniqueBodies">—</div>
          </div>
          <div class="metric-card metric-coordinates flex-grow-1 bounce-in animation-delay-2">
            <div class="metric-label">Последнее обновление</div>
            <div class="metric-number small" id="lastUpdate">—</div>
          </div>
        </div>

        <div class="d-flex gap-2 mb-3 flex-wrap">
          <button class="btn btn-primary ripple" onclick="astronomyUI.loadEvents()">
            <div class="spinner-orbit" id="loadSpinner" style="display:none; width:20px; height:20px; margin: 0 auto;"></div>
            <span id="loadBtnText">↻ Обновить данные</span>
          </button>
        </div>

        <div class="table-responsive">
          <table class="table table-dark table-hover" id="eventsTable">
            <thead>
              <tr>
                <th data-sortable="id">#</th>
                <th data-sortable="body">Тело</th>
                <th data-sortable="event">Событие</th>
                <th data-sortable="when_utc">Когда (UTC)</th>
                <th>Дополнительно</th>
              </tr>
            </thead>
            <tbody id="eventsTableBody">
              <tr>
                <td colspan="5" class="text-center text-muted py-4">
                  <div class="spinner-orbit mx-auto mb-2"></div>
                  Загрузка данных...
                </td>
              </tr>
            </tbody>
          </table>
        </div>

        <div class="mt-3">
          <button class="btn btn-sm btn-outline-light ripple" onclick="astronomyUI.toggleJson()">
            <span id="jsonToggleText">▶ Показать полный JSON</span>
          </button>
          <pre id="fullJson" class="mt-3 p-3 rounded bg-dark text-light small" style="display:none; max-height:400px; overflow:auto;"></pre>
        </div>
      </div>
    </div>
  </div>
</div>

@push('scripts')
<script>
// Инициализация DataTable Manager
let astronomyTable = null;

const astronomyUI = {
  events: [],

  async loadEvents() {
    const spinner = document.getElementById('loadSpinner');
    const btnText = document.getElementById('loadBtnText');
    const tbody = document.getElementById('eventsTableBody');
    
    spinner.style.display = 'block';
    btnText.textContent = 'Загрузка...';
    tbody.innerHTML = '<tr><td colspan="5" class="text-center"><div class="spinner-orbit mx-auto"></div></td></tr>';

    try {
      const response = await fetch('/api/astronomy-events?lat=55.7558&lon=37.6176&days=7');
      const data = await response.json();
      
      if (data.error) {
        tbody.innerHTML = `<tr><td colspan="5" class="text-center text-warning shake">⚠️ ${data.message || data.error}</td></tr>`;
        return;
      }
      
      // Парсим данные из таблицы AstronomyAPI
      this.events = [];
      
      if (data.data && data.data.table && data.data.table.rows) {
        const headers = data.data.table.header || [];
        
        data.data.table.rows.forEach((row, rowIndex) => {
          const bodyName = row.entry?.name || 'Unknown';
          const cells = row.cells || [];
          
          cells.forEach((cell, cellIndex) => {
            if (cell.position && cell.position.horizontal) {
              const azimuth = cell.position.horizontal.azimuth?.degrees || 'N/A';
              const altitude = cell.position.horizontal.altitude?.degrees || 'N/A';
              const distance = cell.distance?.fromEarth?.km || 'N/A';
              
              let eventType = 'Видимость';
              if (parseFloat(altitude) < 0) {
                eventType = 'Под горизонтом';
              } else if (parseFloat(altitude) > 60) {
                eventType = 'Высоко в небе';
              } else if (parseFloat(altitude) < 10) {
                eventType = 'Низко над горизонтом';
              }
              
              this.events.push({
                id: this.events.length + 1,
                body: bodyName,
                event: eventType,
                when_utc: cell.date || headers[cellIndex] || '—',
                extra: `Азимут: ${azimuth}°, Высота: ${altitude}°, Расстояние: ${distance} км`
              });
            }
          });
        });
      }
      
      if (this.events.length === 0) {
        tbody.innerHTML = '<tr><td colspan="5" class="text-center text-muted py-4 shake">Нет событий для отображения</td></tr>';
        return;
      }
      
      // Инициализация DataTable Manager
      if (!astronomyTable) {
        astronomyTable = new DataTableManager({
          tableId: 'eventsTable',
          columns: ['id', 'body', 'event', 'when_utc', 'extra'],
          onRender: (filteredData) => {
            tbody.innerHTML = filteredData.map((row, index) => `
              <tr class="table-row-animated" style="animation-delay: ${index * 0.05}s">
                <td>${row.id}</td>
                <td><strong class="text-info">${row.body}</strong></td>
                <td><span class="badge bg-secondary">${row.event}</span></td>
                <td class="text-muted">${row.when_utc}</td>
                <td class="small text-muted">${row.extra}</td>
              </tr>
            `).join('');
            
            animateTableRows('eventsTable');
          }
        });
        
        // Создание UI элементов управления
        createTableControls(astronomyTable, 'tableControls');
      }
      
      // Загрузка данных в таблицу
      astronomyTable.setData(this.events);
      
      this.updateStats();
      
    } catch (error) {
      console.error('Ошибка загрузки:', error);
      tbody.innerHTML = '<tr><td colspan="5" class="text-center text-danger shake">⚠️ Ошибка загрузки данных</td></tr>';
    } finally {
      spinner.style.display = 'none';
      btnText.textContent = '↻ Обновить данные';
    }
  },

  updateStats() {
    const uniqueBodies = new Set(this.events.map(e => e.body || 'Unknown')).size;
    
    const totalEl = document.getElementById('totalEvents');
    const bodiesEl = document.getElementById('uniqueBodies');
    const updateEl = document.getElementById('lastUpdate');
    
    totalEl.textContent = this.events.length.toLocaleString();
    totalEl.classList.add('highlight-pulse');
    
    bodiesEl.textContent = uniqueBodies;
    bodiesEl.classList.add('highlight-pulse');
    
    updateEl.textContent = new Date().toLocaleTimeString('ru-RU');
    
    setTimeout(() => {
      totalEl.classList.remove('highlight-pulse');
      bodiesEl.classList.remove('highlight-pulse');
    }, 1500);
  },

  toggleJson() {
    const jsonDiv = document.getElementById('fullJson');
    const toggleText = document.getElementById('jsonToggleText');
    
    if (jsonDiv.style.display === 'none') {
      jsonDiv.style.display = 'block';
      jsonDiv.textContent = JSON.stringify(this.events, null, 2);
      toggleText.textContent = '▼ Скрыть JSON';
      jsonDiv.classList.add('slide-in-left');
    } else {
      jsonDiv.style.display = 'none';
      toggleText.textContent = '▶ Показать полный JSON';
    }
  }
};

// Загрузка при старте
document.addEventListener('DOMContentLoaded', () => {
  astronomyUI.loadEvents();
});
</script>
@endpush
@endsection
