use axum::{
    body::Body,
    extract::Request,
    http::StatusCode,
    middleware::Next,
    response::{IntoResponse, Response},
    Json,
};
use std::sync::Arc;
use tokio::sync::Mutex;
use std::collections::HashMap;
use std::time::{Duration, Instant};
use crate::domain::ApiResponse;

/// Rate limiter с использованием token bucket алгоритма
#[derive(Clone)]
pub struct RateLimiter {
    state: Arc<Mutex<RateLimiterState>>,
    tokens_per_second: u32,
}

struct RateLimiterState {
    buckets: HashMap<String, TokenBucket>,
}

struct TokenBucket {
    tokens: f64,
    last_update: Instant,
}

impl RateLimiter {
    pub fn new(tokens_per_second: u32) -> Self {
        Self {
            state: Arc::new(Mutex::new(RateLimiterState {
                buckets: HashMap::new(),
            })),
            tokens_per_second,
        }
    }

    async fn allow(&self, key: &str) -> bool {
        let mut state = self.state.lock().await;
        let bucket = state.buckets
            .entry(key.to_string())
            .or_insert_with(|| TokenBucket {
                tokens: self.tokens_per_second as f64,
                last_update: Instant::now(),
            });

        let now = Instant::now();
        let elapsed = now.duration_since(bucket.last_update).as_secs_f64();
        
        // Пополнение токенов
        bucket.tokens = (bucket.tokens + elapsed * self.tokens_per_second as f64)
            .min(self.tokens_per_second as f64);
        bucket.last_update = now;

        if bucket.tokens >= 1.0 {
            bucket.tokens -= 1.0;
            true
        } else {
            false
        }
    }
}

pub async fn rate_limit_middleware(
    request: Request,
    next: Next,
) -> Response {
    // Извлекаем rate limiter из extensions (будет добавлен при создании роутера)
    let limiter = request.extensions().get::<RateLimiter>().cloned();
    
    if let Some(limiter) = limiter {
        // Используем IP адрес как ключ (в продакшене можно использовать API key)
        let key = request
            .headers()
            .get("x-forwarded-for")
            .and_then(|h| h.to_str().ok())
            .unwrap_or("unknown");

        if !limiter.allow(key).await {
            let error_response = ApiResponse::<()>::error(
                "RATE_LIMIT_EXCEEDED",
                "Too many requests, please try again later",
            );
            
            return (StatusCode::OK, Json(error_response)).into_response();
        }
    }

    next.run(request).await
}

/// Middleware для добавления rate limiter в request extensions
pub async fn add_rate_limiter(
    mut request: Request,
    next: Next,
    limiter: RateLimiter,
) -> Response {
    request.extensions_mut().insert(limiter);
    next.run(request).await
}
