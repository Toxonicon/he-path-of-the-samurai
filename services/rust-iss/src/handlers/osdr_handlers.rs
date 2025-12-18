use axum::{extract::State, Json};
use serde::Serialize;
use crate::{
    domain::{ApiResponse, ApiError, osdr::OsdrItem},
    AppState,
};

#[derive(Serialize)]
struct SyncResponse {
    written: usize,
}

/// GET /osdr/sync - синхронизация данных OSDR
pub async fn sync_osdr(
    State(state): State<AppState>,
) -> Result<Json<ApiResponse<SyncResponse>>, ApiError> {
    let written = state.osdr_service.sync().await?;
    Ok(Json(ApiResponse::success(SyncResponse { written })))
}

#[derive(Serialize)]
struct OsdrListResponse {
    items: Vec<OsdrItem>,
    total: usize,
}

/// GET /osdr/list - получить список элементов OSDR
pub async fn list_osdr(
    State(state): State<AppState>,
) -> Result<Json<ApiResponse<OsdrListResponse>>, ApiError> {
    let limit = std::env::var("OSDR_LIST_LIMIT")
        .ok()
        .and_then(|s| s.parse::<i64>().ok())
        .unwrap_or(20);

    let response = state.osdr_service.list(limit, 0).await?;

    Ok(Json(ApiResponse::success(OsdrListResponse {
        items: response.items,
        total: response.total,
    })))
}
