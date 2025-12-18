#!/bin/sh
set -e
echo "[pascal] compiling legacy.pas"
fpc -O2 -S2 legacy.pas

echo "[pascal] starting legacy CSV generator"
./legacy &
PASCAL_PID=$!

echo "[import] starting import monitor"
# Мониторим скрипты импорта и выполняем их
while true; do
  if [ -f /tmp/do_import.sh ]; then
    chmod +x /tmp/do_import.sh
    echo "[import] Executing import script..."
    /tmp/do_import.sh && echo "[import] Import successful" || echo "[import] Import failed"
    rm /tmp/do_import.sh
  fi
  sleep 2
done
