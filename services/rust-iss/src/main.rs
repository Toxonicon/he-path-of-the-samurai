// Модули приложения
mod config;
mod domain;
mod repo;
mod clients;
mod services;
mod handlers;
mod routes;
mod middleware;

use axum::middleware as axum_middleware;
use config::AppConfig;
use clients::*;
use repo::*;
use services::*;
use services::scheduler::Scheduler;
use middleware::RateLimiter;
use sqlx::postgres::PgPoolOptions;
use std::sync::Arc;
use tracing::{info, Level};
use tracing_subscriber::{EnvFilter, FmtSubscriber};
use tower_http::trace::TraceLayer;

/// Глобальное состояние приложения с DI
#[derive(Clone)]
pub struct AppState {
    pub iss_service: Arc<IssService>,
    pub osdr_service: Arc<OsdrService>,
    pub space_service: Arc<SpaceService>,
}

#[tokio::main]
async fn main() -> anyhow::Result<()> {
    // Инициализация логирования
    let subscriber = FmtSubscriber::builder()
        .with_env_filter(
            EnvFilter::try_from_default_env()
                .unwrap_or_else(|_| EnvFilter::new("info"))
        )
        .with_max_level(Level::INFO)
        .finish();
    
    tracing::subscriber::set_global_default(subscriber)
        .expect("Failed to set tracing subscriber");

    info!("🚀 Starting Cassiopeia Space Monitor v{}", env!("CARGO_PKG_VERSION"));

    // Загрузка конфигурации
    dotenvy::dotenv().ok();
    let config = AppConfig::from_env();
    
    info!("📦 Connecting to database...");
    let pool = PgPoolOptions::new()
        .max_connections(10)
        .connect(&config.database_url)
        .await?;

    info!("🔧 Initializing database schema...");
    init_database(&pool).await?;

    // Инициализация клиентов
    info!("🌐 Initializing API clients...");
    let iss_client = IssClient::new(config.where_iss_url.clone())?;
    let nasa_client = NasaClient::new(config.nasa_api_key.clone())?;
    let spacex_client = SpacexClient::new()?;

    // Инициализация репозиториев
    let iss_repo = IssRepo::new(pool.clone());
    let osdr_repo = OsdrRepo::new(pool.clone());
    let cache_repo = CacheRepo::new(pool.clone());

    // Инициализация сервисов
    let iss_service = Arc::new(IssService::new(iss_repo, iss_client));
    let osdr_service = Arc::new(OsdrService::new(
        osdr_repo,
        nasa_client.clone(),
        config.nasa_api_url.clone(),
    ));
    let space_service = Arc::new(SpaceService::new(
        cache_repo,
        nasa_client,
        spacex_client,
    ));

    // Создание состояния приложения
    let app_state = AppState {
        iss_service: Arc::clone(&iss_service),
        osdr_service: Arc::clone(&osdr_service),
        space_service: Arc::clone(&space_service),
    };

    // Запуск фоновых задач
    info!("⏰ Starting background schedulers...");
    let scheduler = Scheduler::new(
        config.clone(),
        Arc::clone(&iss_service),
        Arc::clone(&osdr_service),
        Arc::clone(&space_service),
    );
    scheduler.start_all();

    // Создание роутера с middleware
    info!("🛣️  Setting up routes and middleware...");
    let rate_limiter = RateLimiter::new(config.rate_limit_per_second);
    
    let app = routes::create_router(app_state)
        .layer(axum_middleware::from_fn(move |req, next| {
            let limiter = rate_limiter.clone();
            async move {
                middleware::add_rate_limiter(req, next, limiter).await
            }
        }))
        .layer(axum_middleware::from_fn(middleware::rate_limit_middleware))
        .layer(TraceLayer::new_for_http());

    // Запуск сервера
    let addr = "0.0.0.0:3000";
    info!("🎯 Server listening on {}", addr);
    info!("✅ Cassiopeia Space Monitor started successfully!");
    
    let listener = tokio::net::TcpListener::bind(addr).await?;
    axum::serve(listener, app.into_make_service()).await?;

    Ok(())
}

/// Инициализация схемы базы данных
async fn init_database(pool: &sqlx::PgPool) -> anyhow::Result<()> {
    // ISS tracking
    sqlx::query(
        "CREATE TABLE IF NOT EXISTS iss_fetch_log(
            id BIGSERIAL PRIMARY KEY,
            fetched_at TIMESTAMPTZ NOT NULL DEFAULT NOW(),
            source_url TEXT NOT NULL,
            payload JSONB NOT NULL
        )"
    )
    .execute(pool)
    .await?;

    sqlx::query("CREATE INDEX IF NOT EXISTS ix_iss_fetched_at ON iss_fetch_log(fetched_at DESC)")
        .execute(pool)
        .await?;

    // OSDR datasets
    sqlx::query(
        "CREATE TABLE IF NOT EXISTS osdr_items(
            id BIGSERIAL PRIMARY KEY,
            dataset_id TEXT,
            title TEXT,
            status TEXT,
            updated_at TIMESTAMPTZ,
            inserted_at TIMESTAMPTZ NOT NULL DEFAULT NOW(),
            raw JSONB NOT NULL
        )"
    )
    .execute(pool)
    .await?;

    sqlx::query(
        "CREATE UNIQUE INDEX IF NOT EXISTS ux_osdr_dataset_id
         ON osdr_items(dataset_id) WHERE dataset_id IS NOT NULL"
    )
    .execute(pool)
    .await?;

    // Universal space cache
    sqlx::query(
        "CREATE TABLE IF NOT EXISTS space_cache(
            id BIGSERIAL PRIMARY KEY,
            source TEXT NOT NULL,
            fetched_at TIMESTAMPTZ NOT NULL DEFAULT NOW(),
            payload JSONB NOT NULL
        )"
    )
    .execute(pool)
    .await?;

    sqlx::query(
        "CREATE INDEX IF NOT EXISTS ix_space_cache_source 
         ON space_cache(source, fetched_at DESC)"
    )
    .execute(pool)
    .await?;

    info!("✅ Database schema initialized");
    Ok(())
}
