# Dedupe OSDR items - remove duplicates by dataset_id keeping newest record
# Run inside Docker container to clean database

Write-Host "Starting OSDR deduplication..." -ForegroundColor Cyan
$sql = @'
-- Remove duplicates, keep one record (newest by inserted_at) per dataset_id
WITH ranked AS (
  SELECT id,
         dataset_id,
         ROW_NUMBER() OVER (PARTITION BY dataset_id ORDER BY COALESCE(updated_at, inserted_at) DESC) AS rn
  FROM osdr_items
  WHERE dataset_id IS NOT NULL
)
DELETE FROM osdr_items
WHERE id IN (SELECT id FROM ranked WHERE rn > 1);

-- Recreate unique index (safe if exists)
CREATE UNIQUE INDEX IF NOT EXISTS ux_osdr_dataset_id
  ON osdr_items(dataset_id) WHERE dataset_id IS NOT NULL;
'@

# Save temp SQL and execute inside DB container
$tmp = "d:\\he-path-of-the-samurai\\osdr-dedupe.sql"
Set-Content -Path $tmp -Value $sql -Encoding UTF8

docker cp $tmp iss_db:/tmp/osdr-dedupe.sql
Write-Host "Executing SQL inside iss_db container..." -ForegroundColor Yellow
docker exec iss_db psql -U monouser -d monolith -f /tmp/osdr-dedupe.sql

Write-Host "Done. Check record counts:"
docker exec iss_db psql -U monouser -d monolith -c "SELECT COUNT(*), COUNT(DISTINCT dataset_id) as unique_ids FROM osdr_items;"
