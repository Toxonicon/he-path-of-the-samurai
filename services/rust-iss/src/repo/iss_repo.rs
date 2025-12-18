use crate::domain::{iss::*, ApiError};
use chrono::{DateTime, Utc};
use serde_json::Value;
use sqlx::{PgPool, Row};

/// Репозиторий для работы с данными МКС
/// Следуем принципу: НИКАКИХ SQL В ХЕНДЛЕРАХ
pub struct IssRepo {
    pool: PgPool,
}

impl IssRepo {
    pub fn new(pool: PgPool) -> Self {
        Self { pool }
    }

    /// Вставка новой записи о позиции МКС
    pub async fn insert(&self, source_url: &str, payload: Value) -> Result<i64, ApiError> {
        let row = sqlx::query(
            "INSERT INTO iss_fetch_log (source_url, payload) VALUES ($1, $2) RETURNING id"
        )
        .bind(source_url)
        .bind(payload)
        .fetch_one(&self.pool)
        .await?;

        Ok(row.get("id"))
    }

    /// Получить последнюю запись
    pub async fn get_last(&self) -> Result<Option<IssPosition>, ApiError> {
        let row_opt = sqlx::query(
            "SELECT id, fetched_at, source_url, payload
             FROM iss_fetch_log
             ORDER BY id DESC LIMIT 1"
        )
        .fetch_optional(&self.pool)
        .await?;

        match row_opt {
            Some(row) => Ok(Some(IssPosition::from_row(&row)?)),
            None => Ok(None),
        }
    }

    /// Получить N последних записей для анализа тренда
    pub async fn get_last_n(&self, n: i64) -> Result<Vec<IssPosition>, ApiError> {
        let rows = sqlx::query(
            "SELECT id, fetched_at, source_url, payload
             FROM iss_fetch_log
             ORDER BY id DESC LIMIT $1"
        )
        .bind(n)
        .fetch_all(&self.pool)
        .await?;

        rows.iter()
            .map(IssPosition::from_row)
            .collect::<Result<Vec<_>, _>>()
            .map_err(|e| ApiError::DatabaseError(e.to_string()))
    }

    /// Получить записи за период
    pub async fn get_by_time_range(
        &self,
        from: DateTime<Utc>,
        to: DateTime<Utc>,
    ) -> Result<Vec<IssPosition>, ApiError> {
        let rows = sqlx::query(
            "SELECT id, fetched_at, source_url, payload
             FROM iss_fetch_log
             WHERE fetched_at BETWEEN $1 AND $2
             ORDER BY fetched_at DESC"
        )
        .bind(from)
        .bind(to)
        .fetch_all(&self.pool)
        .await?;

        rows.iter()
            .map(IssPosition::from_row)
            .collect::<Result<Vec<_>, _>>()
            .map_err(|e| ApiError::DatabaseError(e.to_string()))
    }

    /// Подсчёт общего количества записей
    pub async fn count(&self) -> Result<i64, ApiError> {
        let row = sqlx::query("SELECT COUNT(*) as count FROM iss_fetch_log")
            .fetch_one(&self.pool)
            .await?;

        Ok(row.get("count"))
    }
}
