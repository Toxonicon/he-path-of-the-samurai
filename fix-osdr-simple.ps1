# Простой скрипт для исправления OSDR данных через SQL файл

Write-Host "Загрузка данных NASA OSDR API..." -ForegroundColor Cyan
$response = Invoke-RestMethod -Uri "https://visualization.osdr.nasa.gov/biodata/api/v2/datasets/?format=json"

Write-Host "Получено $($response.PSObject.Properties.Count) датасетов" -ForegroundColor Green

# Создаём SQL файл
$sqlFile = "d:\he-path-of-the-samurai\osdr-insert.sql"
"BEGIN;" | Out-File -FilePath $sqlFile -Encoding UTF8
"TRUNCATE TABLE osdr_items;" | Out-File -FilePath $sqlFile -Append -Encoding UTF8

$count = 0
foreach ($prop in $response.PSObject.Properties) {
    $dataset_id = $prop.Name
    $rest_url = $prop.Value.REST_URL
    
    if ($rest_url) {
        # Экранируем одинарные кавычки в URL
        $rest_url_escaped = $rest_url.Replace("'", "''")
        
        # Создаём JSON строку с правильными кавычками
        $jsonStr = '{"REST_URL":"' + $rest_url_escaped + '"}'
        
        $sql = "INSERT INTO osdr_items (dataset_id, title, status, raw) VALUES ('$dataset_id', '$dataset_id Dataset', 'active', '$jsonStr');"
        $sql | Out-File -FilePath $sqlFile -Append -Encoding UTF8
        $count++
    }
}

"COMMIT;" | Out-File -FilePath $sqlFile -Append -Encoding UTF8

Write-Host "SQL файл создан: $sqlFile" -ForegroundColor Green
Write-Host "Всего команд: $count" -ForegroundColor Cyan

# Копируем в контейнер и выполняем
Write-Host "Копирование SQL в контейнер..." -ForegroundColor Yellow
docker cp $sqlFile iss_db:/tmp/osdr-insert.sql

Write-Host "Выполнение SQL..." -ForegroundColor Yellow
docker exec iss_db psql -U monouser -d monolith -f /tmp/osdr-insert.sql

# Проверка
Write-Host "`nПроверка данных:" -ForegroundColor Cyan
docker exec iss_db psql -U monouser -d monolith -c "SELECT COUNT(*), COUNT(DISTINCT dataset_id) as unique_ids FROM osdr_items;"
docker exec iss_db psql -U monouser -d monolith -c "SELECT dataset_id, title FROM osdr_items LIMIT 5;"
