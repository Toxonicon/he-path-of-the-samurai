# Скрипт для исправления OSDR данных в БД
# Загружает данные с NASA API и вставляет в PostgreSQL

Write-Host "Загрузка данных NASA OSDR API..." -ForegroundColor Cyan
$response = Invoke-RestMethod -Uri "https://visualization.osdr.nasa.gov/biodata/api/v2/datasets/?format=json"

Write-Host "Получено $($response.PSObject.Properties.Count) датасетов" -ForegroundColor Green

# Очистка старых данных
Write-Host "Очистка таблицы osdr_items..." -ForegroundColor Yellow
docker exec iss_db psql -U monouser -d monolith -c "TRUNCATE TABLE osdr_items;"

$count = 0
foreach ($prop in $response.PSObject.Properties) {
    $dataset_id = $prop.Name
    $rest_url = $prop.Value.REST_URL
    
    if ($rest_url) {
        # Создаём правильный JSON объект вручную
        $json_data = "{`"REST_URL`":`"$rest_url`"}".Replace("'", "''")
        
        # SQL для вставки
        $sql = "INSERT INTO osdr_items (dataset_id, title, status, raw) VALUES ('$dataset_id', '$dataset_id Dataset', 'active', '$json_data'::jsonb) ON CONFLICT (dataset_id) DO UPDATE SET raw = EXCLUDED.raw, updated_at = CURRENT_TIMESTAMP;"
        
        # Выполнение через docker exec
        docker exec iss_db psql -U monouser -d monolith -c $sql | Out-Null
        $count++
        
        if ($count % 50 -eq 0) {
            Write-Host "Обработано $count датасетов..." -ForegroundColor Cyan
        }
    }
}

Write-Host "`nВсего вставлено: $count записей" -ForegroundColor Green

# Проверка
Write-Host "`nПроверка данных:" -ForegroundColor Cyan
docker exec iss_db psql -U monouser -d monolith -c "SELECT COUNT(*), COUNT(DISTINCT dataset_id) as unique_ids FROM osdr_items;"
docker exec iss_db psql -U monouser -d monolith -c "SELECT dataset_id, title FROM osdr_items LIMIT 5;"
