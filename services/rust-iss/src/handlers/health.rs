use axum::{extract::State, Json};
use chrono::Utc;
use serde::Serialize;
use crate::{domain::ApiResponse, AppState};

#[derive(Serialize)]
pub struct HealthResponse {
    status: &'static str,
    timestamp: chrono::DateTime<Utc>,
    version: &'static str,
}

pub async fn health_check(State(_state): State<AppState>) -> Json<ApiResponse<HealthResponse>> {
    Json(ApiResponse::success(HealthResponse {
        status: "ok",
        timestamp: Utc::now(),
        version: env!("CARGO_PKG_VERSION"),
    }))
}
