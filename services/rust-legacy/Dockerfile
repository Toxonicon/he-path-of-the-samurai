FROM rust:1.75-slim as builder

WORKDIR /build

# Установка зависимостей для сборки
RUN apt-get update && apt-get install -y \
    pkg-config \
    libssl-dev \
    && rm -rf /var/lib/apt/lists/*

# Копируем манифесты
COPY Cargo.toml Cargo.lock ./

# Создаём dummy main для кэширования зависимостей
RUN mkdir src && echo "fn main() {}" > src/main.rs
RUN cargo build --release && rm -rf src

# Копируем реальный код
COPY src ./src

# Пересобираем с реальным кодом
RUN touch src/main.rs && cargo build --release

# Финальный образ
FROM debian:bookworm-slim

RUN apt-get update && apt-get install -y \
    ca-certificates \
    postgresql-client \
    && rm -rf /var/lib/apt/lists/*

WORKDIR /app

COPY --from=builder /build/target/release/legacy_telemetry /app/legacy_telemetry

# Создаём директорию для CSV
RUN mkdir -p /data/csv

# Entrypoint
COPY entrypoint.sh /app/entrypoint.sh
RUN chmod +x /app/entrypoint.sh

ENTRYPOINT ["/app/entrypoint.sh"]
CMD ["daemon", "--period", "300", "--format", "both"]
