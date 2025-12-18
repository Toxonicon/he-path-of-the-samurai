#!/bin/bash
set -e

echo "🚀 Starting Legacy Telemetry Service"
echo "📊 Output directory: ${CSV_OUT_DIR:-/data/csv}"
echo "⏰ Generation period: ${GEN_PERIOD_SEC:-300}s"

# Ждём готовности БД
echo "⏳ Waiting for database..."
until PGPASSWORD=$PGPASSWORD psql -h "$PGHOST" -U "$PGUSER" -d "$PGDATABASE" -c '\q' 2>/dev/null; do
  echo "Database is unavailable - sleeping"
  sleep 2
done

echo "✅ Database is ready"

# Запускаем приложение
exec /app/legacy_telemetry "$@"
