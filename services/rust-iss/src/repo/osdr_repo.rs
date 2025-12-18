use crate::domain::{osdr::*, ApiError};
use chrono::{DateTime, Utc};
use serde_json::Value;
use sqlx::PgPool;

/// Репозиторий для работы с данными OSDR
pub struct OsdrRepo {
    pool: PgPool,
}

impl OsdrRepo {
    pub fn new(pool: PgPool) -> Self {
        Self { pool }
    }

    /// Upsert по бизнес-ключу (dataset_id)
    /// ОТЛИЧИЕ ОТ СЛЕПЫХ INSERT:
    /// - Избегаем дубликатов
    /// - Обновляем существующие записи при повторном получении
    /// - Используем уникальный индекс для эффективности
    /// - Атомарная операция (нет race conditions)
    pub async fn upsert(
        &self,
        dataset_id: Option<String>,
        title: Option<String>,
        status: Option<String>,
        updated_at: Option<DateTime<Utc>>,
        raw: Value,
    ) -> Result<i64, ApiError> {
        let row = if let Some(ds_id) = dataset_id {
            // Upsert с бизнес-ключом
            sqlx::query(
                "INSERT INTO osdr_items(dataset_id, title, status, updated_at, raw)
                 VALUES($1,$2,$3,$4,$5)
                 ON CONFLICT (dataset_id) DO UPDATE
                 SET title=EXCLUDED.title, 
                     status=EXCLUDED.status,
                     updated_at=EXCLUDED.updated_at, 
                     raw=EXCLUDED.raw
                 RETURNING id"
            )
            .bind(ds_id)
            .bind(title)
            .bind(status)
            .bind(updated_at)
            .bind(raw)
            .fetch_one(&self.pool)
            .await?
        } else {
            // Без dataset_id - обычный insert
            sqlx::query(
                "INSERT INTO osdr_items(dataset_id, title, status, updated_at, raw)
                 VALUES($1,$2,$3,$4,$5)
                 RETURNING id"
            )
            .bind::<Option<String>>(None)
            .bind(title)
            .bind(status)
            .bind(updated_at)
            .bind(raw)
            .fetch_one(&self.pool)
            .await?
        };

        Ok(row.get("id"))
    }

    /// Получить список с пагинацией
    pub async fn list(&self, limit: i64, offset: i64) -> Result<Vec<OsdrItem>, ApiError> {
        let items = sqlx::query_as::<_, OsdrItem>(
            "SELECT id, dataset_id, title, status, updated_at, inserted_at, raw
             FROM osdr_items
             ORDER BY inserted_at DESC
             LIMIT $1 OFFSET $2"
        )
        .bind(limit)
        .bind(offset)
        .fetch_all(&self.pool)
        .await?;

        Ok(items)
    }

    /// Подсчёт общего количества
    pub async fn count(&self) -> Result<i64, ApiError> {
        let row = sqlx::query("SELECT COUNT(*) as count FROM osdr_items")
            .fetch_one(&self.pool)
            .await?;

        Ok(row.get("count"))
    }

    /// Поиск по dataset_id
    pub async fn find_by_dataset_id(&self, dataset_id: &str) -> Result<Option<OsdrItem>, ApiError> {
        let item = sqlx::query_as::<_, OsdrItem>(
            "SELECT id, dataset_id, title, status, updated_at, inserted_at, raw
             FROM osdr_items
             WHERE dataset_id = $1"
        )
        .bind(dataset_id)
        .fetch_optional(&self.pool)
        .await?;

        Ok(item)
    }
}
