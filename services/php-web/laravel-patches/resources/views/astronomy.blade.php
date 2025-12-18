@extends('layouts.app')

@section('content')
<div class="container-fluid">
  <div class="row mb-4">
    <div class="col-12">
      <div class="glass-card p-4">
        <div class="section-header mb-3">
          <span class="section-icon">🌠</span>
          <h3 class="section-title mb-0">Астрономические события (AstronomyAPI)</h3>
        </div>
        <p class="text-muted mb-4">События из AstronomyAPI в реальном времени</p>

        <div class="d-flex gap-3 mb-4 flex-wrap">
          <div class="metric-card metric-velocity flex-grow-1">
            <div class="metric-label">Всего событий</div>
            <div class="metric-number" id="totalEvents">—</div>
          </div>
          <div class="metric-card metric-altitude flex-grow-1">
            <div class="metric-label">Уникальные тела</div>
            <div class="metric-number" id="uniqueBodies">—</div>
          </div>
          <div class="metric-card metric-coordinates flex-grow-1">
            <div class="metric-label">Последнее обновление</div>
            <div class="metric-number small" id="lastUpdate">—</div>
          </div>
        </div>

        <div class="d-flex gap-2 mb-3 flex-wrap">
          <button class="btn btn-primary" onclick="astronomyUI.loadEvents()">
            <span class="spinner" id="loadSpinner" style="display:none"></span>
            ↻ Обновить данные
          </button>
          <select class="form-select" id="bodyFilter" style="max-width:200px">
            <option value="">Все тела</option>
          </select>
          <select class="form-select" id="eventTypeFilter" style="max-width:200px">
            <option value="">Все типы событий</option>
          </select>
        </div>

        <div class="table-responsive">
          <table class="table table-dark table-hover">
            <thead>
              <tr>
                <th>#</th>
                <th>Тело</th>
                <th>Событие</th>
                <th>Когда (UTC)</th>
                <th>Дополнительно</th>
              </tr>
            </thead>
            <tbody id="eventsTableBody">
              <tr>
                <td colspan="5" class="text-center text-muted py-4">
                  <div class="spinner-lg mx-auto mb-2"></div>
                  Загрузка данных...
                </td>
              </tr>
            </tbody>
          </table>
        </div>

        <div class="mt-3">
          <button class="btn btn-sm btn-outline-light" onclick="astronomyUI.toggleJson()">
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
const astronomyUI = {
  events: [],
  filteredEvents: [],

  async loadEvents() {
    const spinner = document.getElementById('loadSpinner');
    const tbody = document.getElementById('eventsTableBody');
    
    spinner.style.display = 'inline-block';
    tbody.innerHTML = '<tr><td colspan="5" class="text-center"><div class="spinner-lg mx-auto"></div></td></tr>';

    try {
      const response = await fetch('/api/astronomy-events?lat=55.7558&lon=37.6176&days=7');
      const data = await response.json();
      
      // Проверяем разные форматы ответа
      if (data.error) {
        tbody.innerHTML = `<tr><td colspan="5" class="text-center text-warning">⚠️ ${data.message || data.error}</td></tr>`;
        return;
      }
      
      // Парсим данные из таблицы AstronomyAPI
      this.events = [];
      
      if (data.data && data.data.table && data.data.table.rows) {
        data.data.table.rows.forEach((row, index) => {
          const cells = row.cells || [];
          const event = {
            id: index + 1,
            body: cells.find(c => c.id === 'body')?.value?.string || cells[0]?.value?.string || 'Unknown',
            event: cells.find(c => c.id === 'position')?.value?.string || cells[1]?.value?.string || '—',
            when_utc: cells.find(c => c.id === 'date')?.value?.string || cells[2]?.value?.string || '—',
            extra: cells.find(c => c.id === 'extra')?.value?.string || cells[3]?.value?.string || ''
          };
          this.events.push(event);
        });
      }
      
      if (this.events.length === 0) {
        tbody.innerHTML = '<tr><td colspan="5" class="text-center text-muted py-4">Нет событий для отображения. Настройте ASTRO_APP_ID и ASTRO_APP_SECRET в .env</td></tr>';
        return;
      }
      
      this.updateStats();
      this.populateFilters();
      this.applyFilters();
      
    } catch (error) {
      console.error('Ошибка загрузки:', error);
      tbody.innerHTML = '<tr><td colspan="5" class="text-center text-danger">⚠️ Ошибка загрузки данных</td></tr>';
    } finally {
      spinner.style.display = 'none';
    }
  },

  updateStats() {
    const uniqueBodies = new Set(this.events.map(e => e.body || 'Unknown')).size;
    document.getElementById('totalEvents').textContent = this.events.length.toLocaleString();
    document.getElementById('uniqueBodies').textContent = uniqueBodies;
    document.getElementById('lastUpdate').textContent = new Date().toLocaleTimeString('ru-RU');
  },

  populateFilters() {
    const bodies = new Set(this.events.map(e => e.body || 'Unknown'));
    const eventTypes = new Set(this.events.map(e => e.event || 'Unknown'));

    const bodyFilter = document.getElementById('bodyFilter');
    const eventTypeFilter = document.getElementById('eventTypeFilter');

    bodyFilter.innerHTML = '<option value="">Все тела</option>';
    bodies.forEach(body => {
      bodyFilter.innerHTML += `<option value="${body}">${body}</option>`;
    });

    eventTypeFilter.innerHTML = '<option value="">Все типы событий</option>';
    eventTypes.forEach(type => {
      eventTypeFilter.innerHTML += `<option value="${type}">${type}</option>`;
    });
  },

  applyFilters() {
    const bodyFilter = document.getElementById('bodyFilter').value;
    const eventTypeFilter = document.getElementById('eventTypeFilter').value;

    this.filteredEvents = this.events.filter(event => {
      const matchBody = !bodyFilter || event.body === bodyFilter;
      const matchType = !eventTypeFilter || event.event === eventTypeFilter;
      return matchBody && matchType;
    });

    this.renderTable();
  },

  renderTable() {
    const tbody = document.getElementById('eventsTableBody');
    
    if (this.filteredEvents.length === 0) {
      tbody.innerHTML = '<tr><td colspan="5" class="text-center text-muted py-4">Нет данных для отображения</td></tr>';
      return;
    }

    tbody.innerHTML = this.filteredEvents.map((event, index) => {
      return `
        <tr class="fade-in" style="animation-delay: ${index * 0.02}s">
          <td>${event.id}</td>
          <td><strong>${event.body}</strong></td>
          <td>${event.event}</td>
          <td>${event.when_utc}</td>
          <td class="small text-muted">${event.extra}</td>
        </tr>
      `;
    }).join('');
  },

  toggleJson() {
    const jsonDiv = document.getElementById('fullJson');
    const toggleText = document.getElementById('jsonToggleText');
    
    if (jsonDiv.style.display === 'none') {
      jsonDiv.style.display = 'block';
      jsonDiv.textContent = JSON.stringify(this.events, null, 2);
      toggleText.textContent = '▼ Скрыть JSON';
    } else {
      jsonDiv.style.display = 'none';
      toggleText.textContent = '▶ Показать полный JSON';
    }
  }
};

// Загрузка при старте
document.addEventListener('DOMContentLoaded', () => {
  astronomyUI.loadEvents();
  
  // Обработчики фильтров
  document.getElementById('bodyFilter').addEventListener('change', () => astronomyUI.applyFilters());
  document.getElementById('eventTypeFilter').addEventListener('change', () => astronomyUI.applyFilters());
});
</script>
@endpush
@endsection
