use axum::{extract::State, Json};
use crate::{
    domain::{ApiResponse, ApiError},
    AppState,
};

/// GET /last - получить последнюю позицию МКС
pub async fn get_last(
    State(state): State<AppState>,
) -> Result<Json<ApiResponse<serde_json::Value>>, ApiError> {
    let position = state.iss_service.get_last_position().await?;

    match position {
        Some(pos) => {
            let data = serde_json::json!({
                "id": pos.id,
                "fetched_at": pos.fetched_at,
                "source_url": pos.source_url,
                "latitude": pos.latitude,
                "longitude": pos.longitude,
                "altitude": pos.altitude,
                "velocity": pos.velocity,
                "payload": pos.payload,
            });
            Ok(Json(ApiResponse::success(data)))
        }
        None => {
            let data = serde_json::json!({"message": "no data"});
            Ok(Json(ApiResponse::success(data)))
        }
    }
}

/// GET /fetch - триггер ручной загрузки данных МКС
pub async fn trigger_fetch(
    State(state): State<AppState>,
) -> Result<Json<ApiResponse<serde_json::Value>>, ApiError> {
    let position = state.iss_service.fetch_and_store().await?;

    let data = serde_json::json!({
        "id": position.id,
        "fetched_at": position.fetched_at,
        "source_url": position.source_url,
        "latitude": position.latitude,
        "longitude": position.longitude,
        "altitude": position.altitude,
        "velocity": position.velocity,
        "payload": position.payload,
    });

    Ok(Json(ApiResponse::success(data)))
}

/// GET /iss/trend - анализ движения МКС
pub async fn get_trend(
    State(state): State<AppState>,
) -> Result<Json<ApiResponse<crate::domain::iss::IssTrend>>, ApiError> {
    let trend = state.iss_service.calculate_trend().await?;
    Ok(Json(ApiResponse::success(trend)))
}
