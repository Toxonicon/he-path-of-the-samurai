use crate::{
    clients::{NasaClient, SpacexClient},
    domain::{space_cache::*, ApiError},
    repo::CacheRepo,
};
use serde_json::{json, Value};

/// Сервис для работы с космическими данными (APOD, NEO, DONKI, SpaceX)
pub struct SpaceService {
    cache_repo: CacheRepo,
    nasa_client: NasaClient,
    spacex_client: SpacexClient,
}

impl SpaceService {
    pub fn new(
        cache_repo: CacheRepo,
        nasa_client: NasaClient,
        spacex_client: SpacexClient,
    ) -> Self {
        Self {
            cache_repo,
            nasa_client,
            spacex_client,
        }
    }

    /// Получить последние данные по источнику
    pub async fn get_latest(&self, source: &str) -> Result<Option<SpaceCacheEntry>, ApiError> {
        self.cache_repo.get_latest(source).await
    }

    /// Обновить APOD
    pub async fn refresh_apod(&self) -> Result<(), ApiError> {
        let payload = self.nasa_client.fetch_apod().await?;
        self.cache_repo.insert("apod", payload).await?;
        Ok(())
    }

    /// Обновить NEO данные
    pub async fn refresh_neo(&self) -> Result<(), ApiError> {
        let payload = self.nasa_client.fetch_neo_feed().await?;
        self.cache_repo.insert("neo", payload).await?;
        Ok(())
    }

    /// Обновить DONKI Flares
    pub async fn refresh_donki_flares(&self) -> Result<(), ApiError> {
        let payload = self.nasa_client.fetch_donki_flares().await?;
        self.cache_repo.insert("flr", payload).await?;
        Ok(())
    }

    /// Обновить DONKI CME
    pub async fn refresh_donki_cme(&self) -> Result<(), ApiError> {
        let payload = self.nasa_client.fetch_donki_cme().await?;
        self.cache_repo.insert("cme", payload).await?;
        Ok(())
    }

    /// Обновить SpaceX данные
    pub async fn refresh_spacex(&self) -> Result<(), ApiError> {
        let payload = self.spacex_client.fetch_next_launch().await?;
        self.cache_repo.insert("spacex", payload).await?;
        Ok(())
    }

    /// Обновить несколько источников
    pub async fn refresh_multiple(&self, sources: &[&str]) -> Result<Vec<String>, ApiError> {
        let mut refreshed = Vec::new();

        for source in sources {
            match *source {
                "apod" => {
                    if let Ok(()) = self.refresh_apod().await {
                        refreshed.push("apod".to_string());
                    }
                }
                "neo" => {
                    if let Ok(()) = self.refresh_neo().await {
                        refreshed.push("neo".to_string());
                    }
                }
                "flr" => {
                    if let Ok(()) = self.refresh_donki_flares().await {
                        refreshed.push("flr".to_string());
                    }
                }
                "cme" => {
                    if let Ok(()) = self.refresh_donki_cme().await {
                        refreshed.push("cme".to_string());
                    }
                }
                "spacex" => {
                    if let Ok(()) = self.refresh_spacex().await {
                        refreshed.push("spacex".to_string());
                    }
                }
                _ => {}
            }
        }

        Ok(refreshed)
    }

    /// Получить сводку по всем источникам
    pub async fn get_summary(&self, osdr_count: i64) -> Result<SpaceSummary, ApiError> {
        let apod = self.get_latest("apod").await?.map(|e| e.payload);
        let neo = self.get_latest("neo").await?.map(|e| e.payload);
        let flr = self.get_latest("flr").await?.map(|e| e.payload);
        let cme = self.get_latest("cme").await?.map(|e| e.payload);
        let spacex = self.get_latest("spacex").await?.map(|e| e.payload);

        Ok(SpaceSummary {
            apod,
            neo,
            flr,
            cme,
            spacex,
            iss: None, // Будет заполнено в хендлере
            osdr_count,
        })
    }
}
