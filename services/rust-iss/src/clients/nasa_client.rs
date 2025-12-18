use super::BaseClient;
use crate::domain::ApiError;
use chrono::Utc;
use serde_json::Value;

/// Клиент для работы с NASA API (OSDR, APOD, NeoWs, DONKI)
pub struct NasaClient {
    base: BaseClient,
    api_key: String,
}

impl NasaClient {
    pub fn new(api_key: String) -> Result<Self, ApiError> {
        // Таймаут 30 сек, до 3 ретраев
        let base = BaseClient::new(30, 3)?;
        
        Ok(Self { base, api_key })
    }

    /// Получить данные OSDR
    pub async fn fetch_osdr(&self, url: &str) -> Result<Value, ApiError> {
        self.base.get_json(url).await
    }

    /// Получить APOD (Astronomy Picture of the Day)
    pub async fn fetch_apod(&self) -> Result<Value, ApiError> {
        let url = "https://api.nasa.gov/planetary/apod";
        
        let mut query = vec![("thumbs", "true")];
        if !self.api_key.is_empty() {
            query.push(("api_key", &self.api_key));
        }
        
        self.base.get_json_with_query(url, &query).await
    }

    /// Получить данные о Near Earth Objects
    pub async fn fetch_neo_feed(&self) -> Result<Value, ApiError> {
        let today = Utc::now().date_naive();
        let start = today - chrono::Days::new(2);
        
        let url = "https://api.nasa.gov/neo/rest/v1/feed";
        let start_str = start.to_string();
        let end_str = today.to_string();
        
        let mut query = vec![
            ("start_date", start_str.as_str()),
            ("end_date", end_str.as_str()),
        ];
        
        if !self.api_key.is_empty() {
            query.push(("api_key", &self.api_key));
        }
        
        self.base.get_json_with_query(url, &query).await
    }

    /// Получить данные DONKI Solar Flares
    pub async fn fetch_donki_flares(&self) -> Result<Value, ApiError> {
        let (from, to) = self.last_days(5);
        let url = "https://api.nasa.gov/DONKI/FLR";
        
        let mut query = vec![
            ("startDate", from.as_str()),
            ("endDate", to.as_str()),
        ];
        
        if !self.api_key.is_empty() {
            query.push(("api_key", &self.api_key));
        }
        
        self.base.get_json_with_query(url, &query).await
    }

    /// Получить данные DONKI Coronal Mass Ejections
    pub async fn fetch_donki_cme(&self) -> Result<Value, ApiError> {
        let (from, to) = self.last_days(5);
        let url = "https://api.nasa.gov/DONKI/CME";
        
        let mut query = vec![
            ("startDate", from.as_str()),
            ("endDate", to.as_str()),
        ];
        
        if !self.api_key.is_empty() {
            query.push(("api_key", &self.api_key));
        }
        
        self.base.get_json_with_query(url, &query).await
    }

    fn last_days(&self, n: i64) -> (String, String) {
        let to = Utc::now().date_naive();
        let from = to - chrono::Days::new(n as u64);
        (from.to_string(), to.to_string())
    }
}
