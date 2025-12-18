use axum::{
    routing::get,
    Router,
};
use tower::ServiceBuilder;
use tower_governor::{
    governor::GovernorConfigBuilder, 
    GovernorLayer,
};
use std::time::Duration;
use crate::{handlers::*, AppState};

pub fn create_router(state: AppState) -> Router {
    // Rate limiting configuration: 100 requests per second
    let governor_conf = Box::new(
        GovernorConfigBuilder::default()
            .per_second(1)
            .burst_size(100)
            .finish()
            .unwrap(),
    );

    let governor_limiter = governor_conf.limiter().clone();
    let governor_layer = GovernorLayer {
        config: Box::leak(governor_conf),
    };

    Router::new()
        // Health check (no rate limit)
        .route("/health", get(health::health_check))
        
        // API endpoints with rate limiting
        .route("/last", get(iss_handlers::get_last))
        .route("/fetch", get(iss_handlers::trigger_fetch))
        .route("/iss/trend", get(iss_handlers::get_trend))
        .route("/osdr/sync", get(osdr_handlers::sync_osdr))
        .route("/osdr/list", get(osdr_handlers::list_osdr))
        .route("/space/:src/latest", get(space_handlers::get_latest))
        .route("/space/refresh", get(space_handlers::refresh_sources))
        .route("/space/summary", get(space_handlers::get_summary))
        .layer(governor_layer)
        
        .with_state(state)
}
