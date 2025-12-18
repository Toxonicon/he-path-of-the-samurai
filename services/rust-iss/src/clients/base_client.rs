use reqwest::{Client, ClientBuilder};
use serde_json::Value;
use std::time::Duration;
use crate::domain::ApiError;
use tracing::{info, warn};

/// Базовый HTTP клиент с retry logic и настройками таймаутов
pub struct BaseClient {
    client: Client,
    max_retries: u32,
}

impl BaseClient {
    pub fn new(timeout_secs: u64, max_retries: u32) -> Result<Self, ApiError> {
        let client = ClientBuilder::new()
            .timeout(Duration::from_secs(timeout_secs))
            .user_agent("Cassiopeia-SpaceMonitor/1.0 (contact: burnfeniks@yandex.ru)")
            .gzip(true)
            .brotli(true)
            .deflate(true)
            .build()
            .map_err(|e| ApiError::InternalError(format!("Failed to build HTTP client: {}", e)))?;

        Ok(Self {
            client,
            max_retries,
        })
    }

    /// GET запрос с автоматическими ретраями
    /// Использует экспоненциальную задержку: 1s, 2s, 4s, 8s
    pub async fn get_json(&self, url: &str) -> Result<Value, ApiError> {
        let mut attempt = 0;
        
        loop {
            attempt += 1;
            
            match self.client.get(url).send().await {
                Ok(response) => {
                    let status = response.status();
                    
                    if status.is_success() {
                        match response.json::<Value>().await {
                            Ok(json) => {
                                info!("Successfully fetched from {}", url);
                                return Ok(json);
                            }
                            Err(e) => {
                                return Err(ApiError::UpstreamError {
                                    code: "INVALID_JSON".to_string(),
                                    message: format!("Failed to parse JSON: {}", e),
                                });
                            }
                        }
                    } else if status.as_u16() == 429 && attempt <= self.max_retries {
                        // Rate limit - retry с большей задержкой
                        let backoff = 2u64.pow(attempt) * 2;
                        warn!("Rate limited (429), retrying in {}s (attempt {}/{})", 
                              backoff, attempt, self.max_retries);
                        tokio::time::sleep(Duration::from_secs(backoff)).await;
                        continue;
                    } else {
                        return Err(ApiError::UpstreamError {
                            code: format!("UPSTREAM_{}", status.as_u16()),
                            message: format!("HTTP error: {}", status),
                        });
                    }
                }
                Err(e) if e.is_timeout() && attempt <= self.max_retries => {
                    let backoff = 2u64.pow(attempt - 1);
                    warn!("Timeout, retrying in {}s (attempt {}/{})", 
                          backoff, attempt, self.max_retries);
                    tokio::time::sleep(Duration::from_secs(backoff)).await;
                    continue;
                }
                Err(e) => {
                    return Err(ApiError::from(e));
                }
            }
        }
    }

    /// GET запрос с query параметрами
    pub async fn get_json_with_query(&self, url: &str, query: &[(&str, &str)]) -> Result<Value, ApiError> {
        let mut attempt = 0;
        
        loop {
            attempt += 1;
            
            match self.client.get(url).query(query).send().await {
                Ok(response) => {
                    let status = response.status();
                    
                    if status.is_success() {
                        match response.json::<Value>().await {
                            Ok(json) => {
                                info!("Successfully fetched from {} with query params", url);
                                return Ok(json);
                            }
                            Err(e) => {
                                return Err(ApiError::UpstreamError {
                                    code: "INVALID_JSON".to_string(),
                                    message: format!("Failed to parse JSON: {}", e),
                                });
                            }
                        }
                    } else if status.as_u16() == 429 && attempt <= self.max_retries {
                        let backoff = 2u64.pow(attempt) * 2;
                        warn!("Rate limited (429), retrying in {}s", backoff);
                        tokio::time::sleep(Duration::from_secs(backoff)).await;
                        continue;
                    } else {
                        return Err(ApiError::UpstreamError {
                            code: format!("UPSTREAM_{}", status.as_u16()),
                            message: format!("HTTP error: {}", status),
                        });
                    }
                }
                Err(e) if e.is_timeout() && attempt <= self.max_retries => {
                    let backoff = 2u64.pow(attempt - 1);
                    warn!("Timeout, retrying in {}s", backoff);
                    tokio::time::sleep(Duration::from_secs(backoff)).await;
                    continue;
                }
                Err(e) => {
                    return Err(ApiError::from(e));
                }
            }
        }
    }
}
