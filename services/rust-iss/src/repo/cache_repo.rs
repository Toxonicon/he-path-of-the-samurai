use crate::domain::{space_cache::*, ApiError};
use serde_json::Value;
use sqlx::{PgPool, Row};

/// Репозиторий для универсального кэша космических данных
pub struct CacheRepo {
    pool: PgPool,
}

impl CacheRepo {
    pub fn new(pool: PgPool) -> Self {
        Self { pool }
    }

    /// Вставка записи в кэш
    pub async fn insert(&self, source: &str, payload: Value) -> Result<i64, ApiError> {
        let row = sqlx::query(
            "INSERT INTO space_cache(source, payload) VALUES ($1, $2) RETURNING id"
        )
        .bind(source)
        .bind(payload)
        .fetch_one(&self.pool)
        .await?;

        Ok(row.get("id"))
    }

    /// Получить последнюю запись по источнику
    pub async fn get_latest(&self, source: &str) -> Result<Option<SpaceCacheEntry>, ApiError> {
        let row_opt = sqlx::query(
            "SELECT id, source, fetched_at, payload FROM space_cache
             WHERE source = $1 ORDER BY id DESC LIMIT 1"
        )
        .bind(source)
        .fetch_optional(&self.pool)
        .await?;

        match row_opt {
            Some(row) => Ok(Some(SpaceCacheEntry {
                id: row.get("id"),
                source: row.get("source"),
                fetched_at: row.get("fetched_at"),
                payload: row.get("payload"),
            })),
            None => Ok(None),
        }
    }

    /// Получить N последних записей по источнику
    pub async fn get_last_n(&self, source: &str, n: i64) -> Result<Vec<SpaceCacheEntry>, ApiError> {
        let rows = sqlx::query(
            "SELECT id, source, fetched_at, payload FROM space_cache
             WHERE source = $1 ORDER BY id DESC LIMIT $2"
        )
        .bind(source)
        .bind(n)
        .fetch_all(&self.pool)
        .await?;

        Ok(rows
            .iter()
            .map(|row| SpaceCacheEntry {
                id: row.get("id"),
                source: row.get("source"),
                fetched_at: row.get("fetched_at"),
                payload: row.get("payload"),
            })
            .collect())
    }

    /// Очистка старых записей (для maintenance)
    pub async fn cleanup_old(&self, source: &str, keep_last: i64) -> Result<u64, ApiError> {
        let result = sqlx::query(
            "DELETE FROM space_cache
             WHERE source = $1
             AND id NOT IN (
                 SELECT id FROM space_cache
                 WHERE source = $1
                 ORDER BY id DESC
                 LIMIT $2
             )"
        )
        .bind(source)
        .bind(keep_last)
        .execute(&self.pool)
        .await?;

        Ok(result.rows_affected())
    }
}
