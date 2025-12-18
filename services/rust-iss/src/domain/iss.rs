use chrono::{DateTime, Utc};
use serde::{Deserialize, Serialize};
use serde_json::Value;

#[derive(Debug, Clone, Serialize, Deserialize)]
pub struct IssPosition {
    pub id: i64,
    pub fetched_at: DateTime<Utc>,
    pub source_url: String,
    pub latitude: Option<f64>,
    pub longitude: Option<f64>,
    pub altitude: Option<f64>,
    pub velocity: Option<f64>,
    pub payload: Value,
}

#[derive(Debug, Serialize, Deserialize)]
pub struct IssTrend {
    pub movement: bool,
    pub delta_km: f64,
    pub dt_sec: f64,
    pub velocity_kmh: Option<f64>,
    pub from_time: Option<DateTime<Utc>>,
    pub to_time: Option<DateTime<Utc>>,
    pub from_lat: Option<f64>,
    pub from_lon: Option<f64>,
    pub to_lat: Option<f64>,
    pub to_lon: Option<f64>,
}

impl IssPosition {
    pub fn from_row(row: &sqlx::postgres::PgRow) -> Result<Self, sqlx::Error> {
        use sqlx::Row;
        
        let payload: Value = row.try_get("payload")?;
        
        Ok(Self {
            id: row.get("id"),
            fetched_at: row.get("fetched_at"),
            source_url: row.get("source_url"),
            latitude: Self::extract_number(&payload, "latitude"),
            longitude: Self::extract_number(&payload, "longitude"),
            altitude: Self::extract_number(&payload, "altitude"),
            velocity: Self::extract_number(&payload, "velocity"),
            payload,
        })
    }

    fn extract_number(value: &Value, key: &str) -> Option<f64> {
        value.get(key).and_then(|v| {
            v.as_f64().or_else(|| v.as_str().and_then(|s| s.parse::<f64>().ok()))
        })
    }
}
