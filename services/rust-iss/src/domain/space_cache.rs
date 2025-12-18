use chrono::{DateTime, Utc};
use serde::{Deserialize, Serialize};
use serde_json::Value;

#[derive(Debug, Clone, Serialize, Deserialize)]
pub struct SpaceCacheEntry {
    pub id: i64,
    pub source: String,
    pub fetched_at: DateTime<Utc>,
    pub payload: Value,
}

#[derive(Debug, Serialize, Deserialize)]
pub struct SpaceSummary {
    pub apod: Option<Value>,
    pub neo: Option<Value>,
    pub flr: Option<Value>,
    pub cme: Option<Value>,
    pub spacex: Option<Value>,
    pub iss: Option<Value>,
    pub osdr_count: i64,
}
