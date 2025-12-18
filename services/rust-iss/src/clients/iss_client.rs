use super::BaseClient;
use crate::domain::ApiError;
use serde_json::Value;

/// Клиент для работы с ISS tracking API
pub struct IssClient {
    base: BaseClient,
    url: String,
}

impl IssClient {
    pub fn new(url: String) -> Result<Self, ApiError> {
        // Таймаут 20 сек, до 3 ретраев
        let base = BaseClient::new(20, 3)?;
        
        Ok(Self { base, url })
    }

    /// Получить текущую позицию МКС
    pub async fn fetch_current_position(&self) -> Result<Value, ApiError> {
        self.base.get_json(&self.url).await
    }
}
