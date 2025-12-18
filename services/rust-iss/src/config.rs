use std::env;

#[derive(Clone)]
pub struct AppConfig {
    pub database_url: String,
    pub nasa_api_url: String,
    pub nasa_api_key: String,
    pub where_iss_url: String,
    
    // Интервалы опроса (в секундах)
    pub fetch_every_osdr: u64,
    pub fetch_every_iss: u64,
    pub fetch_every_apod: u64,
    pub fetch_every_neo: u64,
    pub fetch_every_donki: u64,
    pub fetch_every_spacex: u64,
    
    // Redis настройки
    pub redis_url: Option<String>,
    pub redis_ttl: u64,
    
    // Rate limiting
    pub rate_limit_per_second: u32,
}

impl AppConfig {
    pub fn from_env() -> Self {
        Self {
            database_url: env::var("DATABASE_URL")
                .expect("DATABASE_URL is required"),
            
            nasa_api_url: env::var("NASA_API_URL")
                .unwrap_or_else(|_| "https://visualization.osdr.nasa.gov/biodata/api/v2/datasets/?format=json".to_string()),
            
            nasa_api_key: env::var("NASA_API_KEY")
                .unwrap_or_default(),
            
            where_iss_url: env::var("WHERE_ISS_URL")
                .unwrap_or_else(|_| "https://api.wheretheiss.at/v1/satellites/25544".to_string()),
            
            fetch_every_osdr: env_u64("FETCH_EVERY_SECONDS", 600),
            fetch_every_iss: env_u64("ISS_EVERY_SECONDS", 120),
            fetch_every_apod: env_u64("APOD_EVERY_SECONDS", 43200), // 12ч
            fetch_every_neo: env_u64("NEO_EVERY_SECONDS", 7200),   // 2ч
            fetch_every_donki: env_u64("DONKI_EVERY_SECONDS", 3600), // 1ч
            fetch_every_spacex: env_u64("SPACEX_EVERY_SECONDS", 3600),
            
            redis_url: env::var("REDIS_URL").ok(),
            redis_ttl: env_u64("REDIS_TTL_SECONDS", 300), // 5 мин по умолчанию
            
            rate_limit_per_second: env::var("RATE_LIMIT_PER_SEC")
                .ok()
                .and_then(|s| s.parse().ok())
                .unwrap_or(100),
        }
    }
}

fn env_u64(key: &str, default: u64) -> u64 {
    env::var(key)
        .ok()
        .and_then(|s| s.parse().ok())
        .unwrap_or(default)
}
