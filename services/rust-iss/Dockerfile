# Build stage
FROM rust:1.75-slim AS build
RUN apt-get update && apt-get install -y --no-install-recommends pkg-config libssl-dev ca-certificates && rm -rf /var/lib/apt/lists/*
WORKDIR /app

# кэш зависимостей (Cargo.lock опционален - будет сгенерирован автоматически)
COPY Cargo.toml ./
COPY Cargo.lock* ./
RUN mkdir -p src && printf 'fn main() {}' > src/main.rs && cargo build --release && rm -rf src

# исходники и сборка
COPY src ./src
RUN touch src/main.rs && cargo build --release

# Runtime stage
FROM debian:12-slim
RUN apt-get update && apt-get install -y --no-install-recommends ca-certificates && rm -rf /var/lib/apt/lists/*
ENV RUST_LOG=info
WORKDIR /app
COPY --from=build /app/target/release/rust_iss /usr/local/bin/rust_iss
EXPOSE 3000
CMD ["rust_iss"]
