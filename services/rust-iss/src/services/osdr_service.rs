use crate::{
    clients::NasaClient,
    domain::{osdr::*, ApiError},
    repo::OsdrRepo,
};
use serde_json::Value;
use chrono::{DateTime, NaiveDateTime, TimeZone, Utc};
use uuid::Uuid;

/// Сервис для работы с данными OSDR
pub struct OsdrService {
    repo: OsdrRepo,
    client: NasaClient,
    osdr_url: String,
}

impl OsdrService {
    pub fn new(repo: OsdrRepo, client: NasaClient, osdr_url: String) -> Self {
        Self {
            repo,
            client,
            osdr_url,
        }
    }

    /// Синхронизация данных OSDR
    pub async fn sync(&self) -> Result<usize, ApiError> {
        let json = self.client.fetch_osdr(&self.osdr_url).await?;
        
        // NASA OSDR API возвращает объект с ключами dataset_id
        let items = if let Some(obj) = json.as_object() {
            // Конвертируем объект { "OSD-1": {...}, "OSD-100": {...} } 
            // в массив объектов с добавлением поля dataset_id
            obj.iter().map(|(key, value)| {
                let mut item = value.clone();
                // Добавляем dataset_id как ключ
                if let Some(o) = item.as_object_mut() {
                    o.insert("dataset_id".to_string(), serde_json::Value::String(key.clone()));
                }
                item
            }).collect::<Vec<_>>()
        } else if let Some(a) = json.as_array() {
            a.clone()
        } else if let Some(v) = json.get("items").and_then(|x| x.as_array()) {
            v.clone()
        } else if let Some(v) = json.get("results").and_then(|x| x.as_array()) {
            v.clone()
        } else {
            vec![json.clone()]
        };

        let mut written = 0usize;
        for item in items {
            let orig_dataset_id = extract_string(&item, &["dataset_id", "id", "uuid", "studyId", "accession", "osdr_id"]);
            // Если dataset_id отсутствует — сгенерируем детерминированный fallback
            // из JSON-представления записи (UUID v5 на основе содержимого).
            // Это позволяет избежать множества NULL-записей и дубликатов при последующих sync.
            let dataset_id = match orig_dataset_id {
                Some(s) => Some(s),
                None => {
                    // Используем стабильную генерацию: UUIDv5(namespace OID, raw_json)
                    let raw_text = item.to_string();
                    let generated = format!("gen-{}", Uuid::new_v5(&Uuid::NAMESPACE_OID, raw_text.as_bytes()));
                    Some(generated)
                }
            };
            let title = extract_string(&item, &["title", "name", "label"]);
            let status = extract_string(&item, &["status", "state", "lifecycle"]);
            let updated_at = extract_timestamp(&item, &["updated", "updated_at", "modified", "lastUpdated", "timestamp"]);

            self.repo
                .upsert(dataset_id, title, status, updated_at, item)
                .await?;

            written += 1;
        }

        Ok(written)
    }

    /// Получить список элементов с пагинацией
    pub async fn list(&self, limit: i64, offset: i64) -> Result<OsdrListResponse, ApiError> {
        let items = self.repo.list(limit, offset).await?;
        let total = items.len();

        Ok(OsdrListResponse { items, total })
    }

    /// Получить количество записей
    pub async fn count(&self) -> Result<i64, ApiError> {
        self.repo.count().await
    }
}

/// Извлечение строки из JSON по нескольким возможным ключам
fn extract_string(value: &Value, keys: &[&str]) -> Option<String> {
    for k in keys {
        if let Some(x) = value.get(*k) {
            if let Some(s) = x.as_str() {
                if !s.is_empty() {
                    return Some(s.to_string());
                }
            } else if x.is_number() {
                return Some(x.to_string());
            }
        }
    }
    None
}

/// Извлечение timestamp из JSON
fn extract_timestamp(value: &Value, keys: &[&str]) -> Option<DateTime<Utc>> {
    for k in keys {
        if let Some(x) = value.get(*k) {
            if let Some(s) = x.as_str() {
                // Попытка парсинга ISO 8601
                if let Ok(dt) = s.parse::<DateTime<Utc>>() {
                    return Some(dt);
                }
                // Попытка парсинга других форматов
                if let Ok(ndt) = NaiveDateTime::parse_from_str(s, "%Y-%m-%d %H:%M:%S") {
                    return Some(Utc.from_utc_datetime(&ndt));
                }
            } else if let Some(n) = x.as_i64() {
                return Some(Utc.timestamp_opt(n, 0).single().unwrap_or_else(Utc::now));
            }
        }
    }
    None
}
