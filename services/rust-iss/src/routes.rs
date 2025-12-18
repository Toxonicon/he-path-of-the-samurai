use axum::{
    routing::get,
    Router,
};
use crate::{handlers::*, AppState};

pub fn create_router(state: AppState) -> Router {
    Router::new()
        // Health check
        .route("/health", get(health::health_check))
        
        // ISS endpoints
        .route("/last", get(iss_handlers::get_last))
        .route("/fetch", get(iss_handlers::trigger_fetch))
        .route("/iss/trend", get(iss_handlers::get_trend))
        
        // OSDR endpoints
        .route("/osdr/sync", get(osdr_handlers::sync_osdr))
        .route("/osdr/list", get(osdr_handlers::list_osdr))
        
        // Space cache endpoints
        .route("/space/:src/latest", get(space_handlers::get_latest))
        .route("/space/refresh", get(space_handlers::refresh_sources))
        .route("/space/summary", get(space_handlers::get_summary))
        
        .with_state(state)
}
