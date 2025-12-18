/**
 * Cassiopeia - Data Visualization
 * Графики и визуализация для космических данных
 */

class ISSVisualizer {
    constructor() {
        this.map = null;
        this.marker = null;
        this.trajectory = null;
        this.charts = {};
        this.init();
    }

    async init() {
        await this.initMap();
        await this.initCharts();
        this.startUpdates();
    }

    /**
     * Инициализация карты с позицией МКС
     */
    async initMap() {
        const mapEl = document.getElementById('map');
        if (!mapEl) return;

        // Загружаем текущую позицию
        const data = await this.fetchISSPosition();
        const lat = data.payload?.latitude || 0;
        const lon = data.payload?.longitude || 0;

        // Создаём карту
        this.map = L.map('map').setView([lat, lon], 3);

        // Тёмная тема для карты
        L.tileLayer('https://{s}.basemaps.cartocdn.com/dark_all/{z}/{x}/{y}{r}.png', {
            attribution: '&copy; OpenStreetMap contributors &copy; CARTO',
            subdomains: 'abcd',
            maxZoom: 19
        }).addTo(this.map);

        // Кастомная иконка МКС
        const issIcon = L.divIcon({
            className: 'iss-marker',
            html: '<div style="font-size:32px; text-shadow: 0 0 10px #fff;">🛰️</div>',
            iconSize: [40, 40],
            iconAnchor: [20, 20]
        });

        // Маркер МКС
        this.marker = L.marker([lat, lon], { icon: issIcon })
            .addTo(this.map)
            .bindPopup(this.createPopupContent(data));

        // Траектория
        this.trajectory = L.polyline([], {
            color: '#0d6efd',
            weight: 2,
            opacity: 0.7,
            smoothFactor: 1
        }).addTo(this.map);

        // Загружаем историю траектории
        await this.loadTrajectory();
    }

    /**
     * Загрузка траектории МКС
     */
    async loadTrajectory() {
        try {
            const response = await fetch('/api/iss/trend?hours=2');
            const data = await response.json();
            
            if (data.data && Array.isArray(data.data)) {
                const points = data.data.map(item => [
                    item.payload?.latitude || 0,
                    item.payload?.longitude || 0
                ]).filter(([lat, lon]) => lat !== 0 && lon !== 0);

                this.trajectory.setLatLngs(points);
            }
        } catch (error) {
            console.error('Failed to load trajectory:', error);
        }
    }

    /**
     * Создание popup контента для маркера
     */
    createPopupContent(data) {
        const payload = data.payload || {};
        return `
            <div class="iss-popup">
                <h6 class="mb-2">🛰️ Международная Космическая Станция</h6>
                <table class="table table-sm mb-0">
                    <tr><td>Скорость:</td><td><strong>${this.formatNumber(payload.velocity)} км/ч</strong></td></tr>
                    <tr><td>Высота:</td><td><strong>${this.formatNumber(payload.altitude)} км</strong></td></tr>
                    <tr><td>Широта:</td><td>${(payload.latitude || 0).toFixed(4)}°</td></tr>
                    <tr><td>Долгота:</td><td>${(payload.longitude || 0).toFixed(4)}°</td></tr>
                    <tr><td>Видимость:</td><td>${payload.visibility || 'unknown'}</td></tr>
                </table>
                <div class="small text-muted mt-2">
                    Обновлено: ${new Date(data.created_at || Date.now()).toLocaleTimeString()}
                </div>
            </div>
        `;
    }

    /**
     * Инициализация графиков
     */
    async initCharts() {
        await this.createVelocityChart();
        await this.createAltitudeChart();
    }

    /**
     * График скорости МКС
     */
    async createVelocityChart() {
        const canvas = document.getElementById('issSpeedChart');
        if (!canvas) return;

        const data = await this.fetchTrendData(24);
        const labels = data.map(item => new Date(item.created_at).toLocaleTimeString('ru', { hour: '2-digit', minute: '2-digit' }));
        const values = data.map(item => item.payload?.velocity || 0);

        this.charts.velocity = new Chart(canvas, {
            type: 'line',
            data: {
                labels: labels,
                datasets: [{
                    label: 'Скорость (км/ч)',
                    data: values,
                    borderColor: '#0d6efd',
                    backgroundColor: 'rgba(13, 110, 253, 0.1)',
                    tension: 0.4,
                    fill: true,
                    pointRadius: 0,
                    borderWidth: 2
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        display: false
                    },
                    tooltip: {
                        mode: 'index',
                        intersect: false,
                        callbacks: {
                            label: (context) => `${this.formatNumber(context.parsed.y)} км/ч`
                        }
                    }
                },
                scales: {
                    y: {
                        beginAtZero: false,
                        ticks: {
                            callback: (value) => this.formatNumber(value)
                        }
                    },
                    x: {
                        ticks: {
                            maxTicksLimit: 6
                        }
                    }
                },
                interaction: {
                    mode: 'nearest',
                    axis: 'x',
                    intersect: false
                }
            }
        });
    }

    /**
     * График высоты МКС
     */
    async createAltitudeChart() {
        const canvas = document.getElementById('issAltChart');
        if (!canvas) return;

        const data = await this.fetchTrendData(24);
        const labels = data.map(item => new Date(item.created_at).toLocaleTimeString('ru', { hour: '2-digit', minute: '2-digit' }));
        const values = data.map(item => item.payload?.altitude || 0);

        this.charts.altitude = new Chart(canvas, {
            type: 'line',
            data: {
                labels: labels,
                datasets: [{
                    label: 'Высота (км)',
                    data: values,
                    borderColor: '#198754',
                    backgroundColor: 'rgba(25, 135, 84, 0.1)',
                    tension: 0.4,
                    fill: true,
                    pointRadius: 0,
                    borderWidth: 2
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        display: false
                    },
                    tooltip: {
                        mode: 'index',
                        intersect: false,
                        callbacks: {
                            label: (context) => `${this.formatNumber(context.parsed.y)} км`
                        }
                    }
                },
                scales: {
                    y: {
                        beginAtZero: false,
                        ticks: {
                            callback: (value) => this.formatNumber(value)
                        }
                    },
                    x: {
                        ticks: {
                            maxTicksLimit: 6
                        }
                    }
                }
            }
        });
    }

    /**
     * Получить текущую позицию МКС
     */
    async fetchISSPosition() {
        try {
            const response = await fetch('/api/iss/last');
            return await response.json();
        } catch (error) {
            console.error('Failed to fetch ISS position:', error);
            return { payload: {} };
        }
    }

    /**
     * Получить данные тренда
     */
    async fetchTrendData(hours = 24) {
        try {
            const response = await fetch(`/api/iss/trend?hours=${hours}`);
            const result = await response.json();
            return result.data || [];
        } catch (error) {
            console.error('Failed to fetch trend data:', error);
            return [];
        }
    }

    /**
     * Обновление позиции МКС
     */
    async updatePosition() {
        const data = await this.fetchISSPosition();
        const lat = data.payload?.latitude || 0;
        const lon = data.payload?.longitude || 0;

        if (this.marker && lat !== 0 && lon !== 0) {
            // Плавное перемещение маркера
            this.marker.setLatLng([lat, lon]);
            this.marker.setPopupContent(this.createPopupContent(data));

            // Добавляем точку в траекторию
            const latlngs = this.trajectory.getLatLngs();
            latlngs.push([lat, lon]);
            if (latlngs.length > 100) latlngs.shift(); // Ограничиваем длину
            this.trajectory.setLatLngs(latlngs);

            // Центрируем карту (плавно)
            this.map.panTo([lat, lon], { animate: true, duration: 1 });
        }

        // Обновляем метрики
        this.updateMetrics(data);
    }

    /**
     * Обновление метрик на странице
     */
    updateMetrics(data) {
        const payload = data.payload || {};
        
        const metrics = {
            velocity: payload.velocity,
            altitude: payload.altitude,
            latitude: payload.latitude,
            longitude: payload.longitude
        };

        Object.entries(metrics).forEach(([key, value]) => {
            const el = document.querySelector(`[data-metric="${key}"]`);
            if (el && value !== undefined) {
                el.classList.add('updating');
                el.textContent = this.formatNumber(value);
                setTimeout(() => el.classList.remove('updating'), 400);
            }
        });
    }

    /**
     * Обновление графиков
     */
    async updateCharts() {
        const data = await this.fetchTrendData(24);
        
        if (this.charts.velocity) {
            const labels = data.map(item => new Date(item.created_at).toLocaleTimeString('ru', { hour: '2-digit', minute: '2-digit' }));
            const velocities = data.map(item => item.payload?.velocity || 0);
            
            this.charts.velocity.data.labels = labels;
            this.charts.velocity.data.datasets[0].data = velocities;
            this.charts.velocity.update('none'); // Без анимации
        }

        if (this.charts.altitude) {
            const labels = data.map(item => new Date(item.created_at).toLocaleTimeString('ru', { hour: '2-digit', minute: '2-digit' }));
            const altitudes = data.map(item => item.payload?.altitude || 0);
            
            this.charts.altitude.data.labels = labels;
            this.charts.altitude.data.datasets[0].data = altitudes;
            this.charts.altitude.update('none');
        }
    }

    /**
     * Запуск периодических обновлений
     */
    startUpdates() {
        // Обновление позиции каждые 10 секунд
        setInterval(() => this.updatePosition(), 10000);

        // Обновление графиков каждую минуту
        setInterval(() => this.updateCharts(), 60000);
    }

    /**
     * Форматирование чисел
     */
    formatNumber(num) {
        if (num === undefined || num === null) return '—';
        return Math.round(num).toLocaleString('ru');
    }
}

/**
 * OSDR Statistics Visualizer
 */
class OSDRVisualizer {
    constructor() {
        this.chart = null;
    }

    async init() {
        await this.createStatsChart();
    }

    async createStatsChart() {
        const canvas = document.getElementById('osdrStatsChart');
        if (!canvas) return;

        try {
            const response = await fetch('/api/osdr/stats');
            const stats = await response.json();

            // Группируем по типу данных
            const types = {};
            stats.data?.forEach(item => {
                const type = item.data_type || 'Other';
                types[type] = (types[type] || 0) + 1;
            });

            this.chart = new Chart(canvas, {
                type: 'doughnut',
                data: {
                    labels: Object.keys(types),
                    datasets: [{
                        data: Object.values(types),
                        backgroundColor: [
                            '#0d6efd', '#198754', '#ffc107', 
                            '#dc3545', '#6f42c1', '#fd7e14'
                        ]
                    }]
                },
                options: {
                    responsive: true,
                    plugins: {
                        legend: {
                            position: 'bottom'
                        }
                    }
                }
            });
        } catch (error) {
            console.error('Failed to create OSDR stats chart:', error);
        }
    }
}

// Глобальная инициализация
let issVisualizer, osdrVisualizer;

document.addEventListener('DOMContentLoaded', () => {
    issVisualizer = new ISSVisualizer();
    osdrVisualizer = new OSDRVisualizer();
    osdrVisualizer.init();
});
