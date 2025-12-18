use super::BaseClient;
use crate::domain::ApiError;
use serde_json::Value;

/// Клиент для работы с SpaceX API
pub struct SpacexClient {
    base: BaseClient,
}

impl SpacexClient {
    pub fn new() -> Result<Self, ApiError> {
        // Таймаут 30 сек, до 3 ретраев
        let base = BaseClient::new(30, 3)?;
        
        Ok(Self { base })
    }

    /// Получить данные о следующем запуске
    pub async fn fetch_next_launch(&self) -> Result<Value, ApiError> {
        let url = "https://api.spacexdata.com/v4/launches/next";
        self.base.get_json(url).await
    }

    /// Получить последние запуски
    pub async fn fetch_latest_launches(&self, limit: usize) -> Result<Value, ApiError> {
        let url = format!("https://api.spacexdata.com/v4/launches/past?limit={}", limit);
        self.base.get_json(&url).await
    }
}
