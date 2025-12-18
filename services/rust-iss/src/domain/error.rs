use axum::{
    http::StatusCode,
    response::{IntoResponse, Response},
    Json,
};
use serde::{Deserialize, Serialize};
use std::fmt;
use uuid::Uuid;

/// Единый формат ошибок согласно требованиям
#[derive(Debug, Serialize, Deserialize)]
pub struct ApiResponse<T> {
    pub ok: bool,
    #[serde(skip_serializing_if = "Option::is_none")]
    pub data: Option<T>,
    #[serde(skip_serializing_if = "Option::is_none")]
    pub error: Option<ErrorDetail>,
}

#[derive(Debug, Serialize, Deserialize)]
pub struct ErrorDetail {
    pub code: String,
    pub message: String,
    pub trace_id: String,
}

#[derive(Debug)]
pub enum ApiError {
    DatabaseError(String),
    UpstreamError { code: String, message: String },
    ValidationError(String),
    NotFound(String),
    RateLimitExceeded,
    InternalError(String),
}

impl fmt::Display for ApiError {
    fn fmt(&self, f: &mut fmt::Formatter<'_>) -> fmt::Result {
        match self {
            ApiError::DatabaseError(msg) => write!(f, "Database error: {}", msg),
            ApiError::UpstreamError { message, .. } => write!(f, "Upstream error: {}", message),
            ApiError::ValidationError(msg) => write!(f, "Validation error: {}", msg),
            ApiError::NotFound(msg) => write!(f, "Not found: {}", msg),
            ApiError::RateLimitExceeded => write!(f, "Rate limit exceeded"),
            ApiError::InternalError(msg) => write!(f, "Internal error: {}", msg),
        }
    }
}

impl IntoResponse for ApiError {
    fn into_response(self) -> Response {
        let trace_id = Uuid::new_v4().to_string();
        
        let (code, message) = match &self {
            ApiError::DatabaseError(msg) => ("DATABASE_ERROR", msg.clone()),
            ApiError::UpstreamError { code, message } => (code.as_str(), message.clone()),
            ApiError::ValidationError(msg) => ("VALIDATION_ERROR", msg.clone()),
            ApiError::NotFound(msg) => ("NOT_FOUND", msg.clone()),
            ApiError::RateLimitExceeded => ("RATE_LIMIT_EXCEEDED", "Too many requests".to_string()),
            ApiError::InternalError(msg) => ("INTERNAL_ERROR", msg.clone()),
        };

        tracing::error!("API Error [{}]: {} - {}", trace_id, code, message);

        let body = Json(ApiResponse::<()> {
            ok: false,
            data: None,
            error: Some(ErrorDetail {
                code: code.to_string(),
                message,
                trace_id,
            }),
        });

        // Всегда возвращаем HTTP 200 согласно требованиям
        (StatusCode::OK, body).into_response()
    }
}

impl<T> ApiResponse<T> {
    pub fn success(data: T) -> Self {
        Self {
            ok: true,
            data: Some(data),
            error: None,
        }
    }

    pub fn error(code: impl Into<String>, message: impl Into<String>) -> Self {
        Self {
            ok: false,
            data: None,
            error: Some(ErrorDetail {
                code: code.into(),
                message: message.into(),
                trace_id: Uuid::new_v4().to_string(),
            }),
        }
    }
}

impl From<sqlx::Error> for ApiError {
    fn from(err: sqlx::Error) -> Self {
        ApiError::DatabaseError(err.to_string())
    }
}

impl From<reqwest::Error> for ApiError {
    fn from(err: reqwest::Error) -> Self {
        let code = if err.is_timeout() {
            "UPSTREAM_TIMEOUT"
        } else if err.is_status() {
            match err.status() {
                Some(status) if status.as_u16() == 403 => "UPSTREAM_403",
                Some(status) if status.as_u16() == 429 => "UPSTREAM_429",
                Some(status) => return ApiError::UpstreamError {
                    code: format!("UPSTREAM_{}", status.as_u16()),
                    message: err.to_string(),
                },
                None => "UPSTREAM_ERROR",
            }
        } else {
            "UPSTREAM_ERROR"
        };
        
        ApiError::UpstreamError {
            code: code.to_string(),
            message: err.to_string(),
        }
    }
}
