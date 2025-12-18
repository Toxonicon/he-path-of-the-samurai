/**
 * ============================================
 * CASSIOPEIA DATA TABLE MANAGER
 * Универсальная библиотека для работы с таблицами
 * ============================================
 */

class DataTableManager {
    constructor(options) {
        this.tableId = options.tableId;
        this.data = [];
        this.filteredData = [];
        this.currentSort = { column: null, direction: 'asc' };
        this.filters = {};
        this.searchQuery = '';
        this.onRender = options.onRender || null;
        this.columns = options.columns || [];
        
        this.init();
    }

    /**
     * Инициализация
     */
    init() {
        this.attachEventListeners();
    }

    /**
     * Установка данных
     */
    setData(data) {
        this.data = data;
        this.filteredData = [...data];
        this.applyFiltersAndSort();
    }

    /**
     * Прикрепление обработчиков событий
     */
    attachEventListeners() {
        const table = document.getElementById(this.tableId);
        if (!table) return;

        // Сортировка по клику на заголовок
        const headers = table.querySelectorAll('th[data-sortable]');
        headers.forEach(header => {
            header.style.cursor = 'pointer';
            header.innerHTML += ' <span class="sort-icon">⇅</span>';
            
            header.addEventListener('click', () => {
                const column = header.dataset.sortable;
                this.sort(column);
            });
        });
    }

    /**
     * Сортировка
     */
    sort(column) {
        if (this.currentSort.column === column) {
            // Переключение направления
            this.currentSort.direction = this.currentSort.direction === 'asc' ? 'desc' : 'asc';
        } else {
            this.currentSort.column = column;
            this.currentSort.direction = 'asc';
        }

        this.applyFiltersAndSort();
        this.updateSortIcons();
    }

    /**
     * Обновление иконок сортировки
     */
    updateSortIcons() {
        const table = document.getElementById(this.tableId);
        if (!table) return;

        const headers = table.querySelectorAll('th[data-sortable]');
        headers.forEach(header => {
            const icon = header.querySelector('.sort-icon');
            const column = header.dataset.sortable;

            if (column === this.currentSort.column) {
                icon.textContent = this.currentSort.direction === 'asc' ? '▲' : '▼';
                icon.style.opacity = '1';
            } else {
                icon.textContent = '⇅';
                icon.style.opacity = '0.3';
            }
        });
    }

    /**
     * Фильтрация
     */
    addFilter(column, value) {
        if (value === '' || value === null) {
            delete this.filters[column];
        } else {
            this.filters[column] = value;
        }
        this.applyFiltersAndSort();
    }

    /**
     * Фильтрация по дате
     */
    addDateFilter(column, startDate, endDate) {
        this.filters[column] = { type: 'date', start: startDate, end: endDate };
        this.applyFiltersAndSort();
    }

    /**
     * Поиск
     */
    search(query) {
        this.searchQuery = query.toLowerCase();
        this.applyFiltersAndSort();
    }

    /**
     * Применение всех фильтров и сортировки
     */
    applyFiltersAndSort() {
        let result = [...this.data];

        // Применяем фильтры
        Object.keys(this.filters).forEach(column => {
            const filterValue = this.filters[column];

            result = result.filter(row => {
                const cellValue = this.getCellValue(row, column);

                if (filterValue.type === 'date') {
                    const date = new Date(cellValue);
                    const start = filterValue.start ? new Date(filterValue.start) : null;
                    const end = filterValue.end ? new Date(filterValue.end) : null;

                    if (start && date < start) return false;
                    if (end && date > end) return false;
                    return true;
                } else {
                    return String(cellValue).toLowerCase().includes(String(filterValue).toLowerCase());
                }
            });
        });

        // Применяем поиск
        if (this.searchQuery) {
            result = result.filter(row => {
                return this.columns.some(column => {
                    const value = this.getCellValue(row, column);
                    return String(value).toLowerCase().includes(this.searchQuery);
                });
            });
        }

        // Применяем сортировку
        if (this.currentSort.column) {
            result.sort((a, b) => {
                const aVal = this.getCellValue(a, this.currentSort.column);
                const bVal = this.getCellValue(b, this.currentSort.column);

                // Определяем тип данных
                const aNum = parseFloat(aVal);
                const bNum = parseFloat(bVal);

                let comparison = 0;

                if (!isNaN(aNum) && !isNaN(bNum)) {
                    // Числовое сравнение
                    comparison = aNum - bNum;
                } else if (this.isDate(aVal) && this.isDate(bVal)) {
                    // Сравнение дат
                    comparison = new Date(aVal) - new Date(bVal);
                } else {
                    // Строковое сравнение
                    comparison = String(aVal).localeCompare(String(bVal));
                }

                return this.currentSort.direction === 'asc' ? comparison : -comparison;
            });
        }

        this.filteredData = result;
        this.render();
    }

    /**
     * Получение значения ячейки
     */
    getCellValue(row, column) {
        if (column.includes('.')) {
            // Вложенное свойство (например, "position.latitude")
            return column.split('.').reduce((obj, key) => obj?.[key], row);
        }
        return row[column];
    }

    /**
     * Проверка даты
     */
    isDate(value) {
        const date = new Date(value);
        return date instanceof Date && !isNaN(date);
    }

    /**
     * Рендеринг
     */
    render() {
        if (this.onRender) {
            this.onRender(this.filteredData);
        }
    }

    /**
     * Экспорт в CSV
     */
    exportToCSV(filename = 'export.csv') {
        const headers = this.columns.join(',');
        const rows = this.filteredData.map(row => {
            return this.columns.map(column => {
                const value = this.getCellValue(row, column);
                // Экранируем значения с запятыми
                return `"${String(value).replace(/"/g, '""')}"`;
            }).join(',');
        });

        const csv = [headers, ...rows].join('\n');
        const blob = new Blob([csv], { type: 'text/csv;charset=utf-8;' });
        const link = document.createElement('a');
        const url = URL.createObjectURL(blob);

        link.setAttribute('href', url);
        link.setAttribute('download', filename);
        link.style.visibility = 'hidden';
        document.body.appendChild(link);
        link.click();
        document.body.removeChild(link);
    }

    /**
     * Получение статистики
     */
    getStats() {
        return {
            total: this.data.length,
            filtered: this.filteredData.length,
            hidden: this.data.length - this.filteredData.length
        };
    }

    /**
     * Очистка всех фильтров
     */
    clearFilters() {
        this.filters = {};
        this.searchQuery = '';
        this.applyFiltersAndSort();
    }

    /**
     * Очистка сортировки
     */
    clearSort() {
        this.currentSort = { column: null, direction: 'asc' };
        this.applyFiltersAndSort();
        this.updateSortIcons();
    }
}

/**
 * ============================================
 * ДОПОЛНИТЕЛЬНЫЕ УТИЛИТЫ
 * ============================================
 */

/**
 * Создание элементов управления таблицей
 */
function createTableControls(tableManager, containerId) {
    const container = document.getElementById(containerId);
    if (!container) return;

    const html = `
        <div class="table-controls glass-card p-3 mb-3">
            <div class="row g-3">
                <!-- Поиск -->
                <div class="col-md-4">
                    <div class="input-group">
                        <span class="input-group-text bg-dark border-secondary">
                            <i class="bi bi-search"></i> 🔍
                        </span>
                        <input type="text" 
                               class="form-control bg-dark text-light border-secondary" 
                               id="tableSearch" 
                               placeholder="Поиск...">
                    </div>
                </div>

                <!-- Сортировка -->
                <div class="col-md-3">
                    <select class="form-select bg-dark text-light border-secondary" id="sortColumn">
                        <option value="">Сортировать по...</option>
                        ${tableManager.columns.map(col => 
                            `<option value="${col}">${col}</option>`
                        ).join('')}
                    </select>
                </div>

                <div class="col-md-2">
                    <select class="form-select bg-dark text-light border-secondary" id="sortDirection">
                        <option value="asc">↑ Возрастание</option>
                        <option value="desc">↓ Убывание</option>
                    </select>
                </div>

                <!-- Действия -->
                <div class="col-md-3">
                    <div class="btn-group w-100" role="group">
                        <button type="button" class="btn btn-outline-light btn-sm" id="clearFilters">
                            🗑️ Очистить
                        </button>
                        <button type="button" class="btn btn-outline-light btn-sm" id="exportCSV">
                            📥 CSV
                        </button>
                    </div>
                </div>
            </div>

            <!-- Статистика -->
            <div class="mt-2 small text-muted" id="tableStats">
                Показано: <span id="filteredCount">0</span> из <span id="totalCount">0</span>
            </div>
        </div>
    `;

    container.innerHTML = html;

    // Обработчики событий
    document.getElementById('tableSearch').addEventListener('input', (e) => {
        tableManager.search(e.target.value);
        updateStats();
    });

    document.getElementById('sortColumn').addEventListener('change', (e) => {
        if (e.target.value) {
            tableManager.sort(e.target.value);
        }
    });

    document.getElementById('sortDirection').addEventListener('change', (e) => {
        if (tableManager.currentSort.column) {
            tableManager.currentSort.direction = e.target.value;
            tableManager.applyFiltersAndSort();
        }
    });

    document.getElementById('clearFilters').addEventListener('click', () => {
        tableManager.clearFilters();
        tableManager.clearSort();
        document.getElementById('tableSearch').value = '';
        document.getElementById('sortColumn').value = '';
        updateStats();
    });

    document.getElementById('exportCSV').addEventListener('click', () => {
        const filename = `cassiopeia_export_${new Date().toISOString().slice(0,10)}.csv`;
        tableManager.exportToCSV(filename);
    });

    function updateStats() {
        const stats = tableManager.getStats();
        document.getElementById('filteredCount').textContent = stats.filtered;
        document.getElementById('totalCount').textContent = stats.total;
    }

    updateStats();
}

/**
 * Анимация появления строк таблицы
 */
function animateTableRows(tableId) {
    const rows = document.querySelectorAll(`#${tableId} tbody tr`);
    rows.forEach((row, index) => {
        row.classList.add('table-row-animated');
        row.style.animationDelay = `${index * 0.05}s`;
    });
}

/**
 * Подсветка совпадений при поиске
 */
function highlightSearchResults(text, query) {
    if (!query) return text;
    
    const regex = new RegExp(`(${query})`, 'gi');
    return text.replace(regex, '<mark class="bg-warning text-dark">$1</mark>');
}
