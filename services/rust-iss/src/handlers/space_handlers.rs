use axum::{
    extract::{Path, Query, State},
    Json,
};
use serde::{Deserialize, Serialize};
use serde_json::Value;
use std::collections::HashMap;
use crate::{
    domain::{ApiResponse, ApiError},
    AppState,
};

#[derive(Serialize)]
struct LatestResponse {
    source: String,
    fetched_at: Option<chrono::DateTime<chrono::Utc>>,
    payload: Option<Value>,
}

/// GET /space/:src/latest - получить последние данные по источнику
pub async fn get_latest(
    Path(source): Path<String>,
    State(state): State<AppState>,
) -> Result<Json<ApiResponse<LatestResponse>>, ApiError> {
    let entry = state.space_service.get_latest(&source).await?;

    match entry {
        Some(e) => Ok(Json(ApiResponse::success(LatestResponse {
            source,
            fetched_at: Some(e.fetched_at),
            payload: Some(e.payload),
        }))),
        None => Ok(Json(ApiResponse::success(LatestResponse {
            source,
            fetched_at: None,
            payload: None,
        }))),
    }
}

#[derive(Deserialize)]
pub struct RefreshQuery {
    #[serde(default = "default_sources")]
    src: String,
}

fn default_sources() -> String {
    "apod,neo,flr,cme,spacex".to_string()
}

#[derive(Serialize)]
struct RefreshResponse {
    refreshed: Vec<String>,
}

/// GET /space/refresh?src=apod,neo,flr,cme,spacex - обновить данные
pub async fn refresh_sources(
    Query(query): Query<RefreshQuery>,
    State(state): State<AppState>,
) -> Result<Json<ApiResponse<RefreshResponse>>, ApiError> {
    let sources: Vec<&str> = query.src.split(',').map(|s| s.trim()).collect();
    let refreshed = state.space_service.refresh_multiple(&sources).await?;

    Ok(Json(ApiResponse::success(RefreshResponse { refreshed })))
}

/// GET /space/summary - сводка по всем источникам
pub async fn get_summary(
    State(state): State<AppState>,
) -> Result<Json<ApiResponse<Value>>, ApiError> {
    let osdr_count = state.osdr_service.count().await?;
    let mut summary = state.space_service.get_summary(osdr_count).await?;

    // Добавляем данные ISS
    if let Ok(Some(iss_pos)) = state.iss_service.get_last_position().await {
        summary.iss = Some(serde_json::json!({
            "at": iss_pos.fetched_at,
            "payload": iss_pos.payload,
        }));
    }

    let data = serde_json::json!({
        "apod": summary.apod.unwrap_or(serde_json::json!({})),
        "neo": summary.neo.unwrap_or(serde_json::json!({})),
        "flr": summary.flr.unwrap_or(serde_json::json!({})),
        "cme": summary.cme.unwrap_or(serde_json::json!({})),
        "spacex": summary.spacex.unwrap_or(serde_json::json!({})),
        "iss": summary.iss.unwrap_or(serde_json::json!({})),
        "osdr_count": summary.osdr_count,
    });

    Ok(Json(ApiResponse::success(data)))
}
