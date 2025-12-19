# Скрипт для удаления дубликатов в таблице osdr_items и восстановление уникального индекса
# Удаляем дубликаты по dataset_id (оставляем самую свежую запись по inserted_at), затем создаём уникальный индекс

Write-Host "Запуск очистки дубликатов osdr_items..." -ForegroundColor Cyan
$sql = @'
-- Удаляем дубликаты, оставляя одну запись (самую новую по inserted_at) для каждого dataset_id
WITH ranked AS (
  SELECT id,
         dataset_id,
         ROW_NUMBER() OVER (PARTITION BY dataset_id ORDER BY COALESCE(updated_at, inserted_at) DESC) AS rn
  FROM osdr_items
  WHERE dataset_id IS NOT NULL
)
DELETE FROM osdr_items
WHERE id IN (SELECT id FROM ranked WHERE rn > 1);

-- Пересоздаём уникальный индекс (если уже есть - команда не упадёт)
CREATE UNIQUE INDEX IF NOT EXISTS ux_osdr_dataset_id
  ON osdr_items(dataset_id) WHERE dataset_id IS NOT NULL;
'@

# Сохраним временный SQL и выполним внутри контейнера БД
$tmp = "d:\\he-path-of-the-samurai\\osdr-dedupe.sql"
Set-Content -Path $tmp -Value $sql -Encoding UTF8

docker cp $tmp iss_db:/tmp/osdr-dedupe.sql
Write-Host "Выполняю SQL внутри контейнера iss_db..." -ForegroundColor Yellow
docker exec iss_db psql -U monouser -d monolith -f /tmp/osdr-dedupe.sql

Write-Host "Готово. Проверьте количество записей:"
docker exec iss_db psql -U monouser -d monolith -c "SELECT COUNT(*), COUNT(DISTINCT dataset_id) as unique_ids FROM osdr_items;"
