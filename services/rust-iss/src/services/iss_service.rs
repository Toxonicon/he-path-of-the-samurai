use crate::{
    clients::IssClient,
    domain::{iss::*, ApiError},
    repo::IssRepo,
};

/// Сервис для работы с данными МКС
pub struct IssService {
    repo: IssRepo,
    client: IssClient,
}

impl IssService {
    pub fn new(repo: IssRepo, client: IssClient) -> Self {
        Self { repo, client }
    }

    /// Получить последнюю позицию МКС
    pub async fn get_last_position(&self) -> Result<Option<IssPosition>, ApiError> {
        self.repo.get_last().await
    }

    /// Триггер ручной загрузки данных МКС
    pub async fn fetch_and_store(&self) -> Result<IssPosition, ApiError> {
        let payload = self.client.fetch_current_position().await?;
        let source_url = "https://api.wheretheiss.at/v1/satellites/25544";
        
        let id = self.repo.insert(source_url, payload.clone()).await?;
        
        // Получаем только что вставленную запись
        self.repo.get_last().await?
            .ok_or_else(|| ApiError::InternalError("Failed to retrieve inserted record".to_string()))
    }

    /// Вычислить тренд движения МКС
    pub async fn calculate_trend(&self) -> Result<IssTrend, ApiError> {
        let positions = self.repo.get_last_n(2).await?;

        if positions.len() < 2 {
            return Ok(IssTrend {
                movement: false,
                delta_km: 0.0,
                dt_sec: 0.0,
                velocity_kmh: None,
                from_time: None,
                to_time: None,
                from_lat: None,
                from_lon: None,
                to_lat: None,
                to_lon: None,
            });
        }

        let recent = &positions[0];
        let previous = &positions[1];

        let mut delta_km = 0.0;
        let mut movement = false;

        if let (Some(lat1), Some(lon1), Some(lat2), Some(lon2)) = (
            previous.latitude,
            previous.longitude,
            recent.latitude,
            recent.longitude,
        ) {
            delta_km = haversine_km(lat1, lon1, lat2, lon2);
            movement = delta_km > 0.1;
        }

        let dt_sec = (recent.fetched_at - previous.fetched_at).num_milliseconds() as f64 / 1000.0;

        Ok(IssTrend {
            movement,
            delta_km,
            dt_sec,
            velocity_kmh: recent.velocity,
            from_time: Some(previous.fetched_at),
            to_time: Some(recent.fetched_at),
            from_lat: previous.latitude,
            from_lon: previous.longitude,
            to_lat: recent.latitude,
            to_lon: recent.longitude,
        })
    }
}

/// Формула гаверсинуса для вычисления расстояния между двумя точками на сфере
fn haversine_km(lat1: f64, lon1: f64, lat2: f64, lon2: f64) -> f64 {
    let rlat1 = lat1.to_radians();
    let rlat2 = lat2.to_radians();
    let dlat = (lat2 - lat1).to_radians();
    let dlon = (lon2 - lon1).to_radians();
    
    let a = (dlat / 2.0).sin().powi(2) 
        + rlat1.cos() * rlat2.cos() * (dlon / 2.0).sin().powi(2);
    let c = 2.0 * a.sqrt().atan2((1.0 - a).sqrt());
    
    6371.0 * c // Радиус Земли в км
}
