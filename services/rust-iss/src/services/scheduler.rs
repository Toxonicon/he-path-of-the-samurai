use crate::{
    config::AppConfig,
    services::{IssService, OsdrService, SpaceService},
};
use std::time::Duration;
use tokio::sync::Mutex;
use std::sync::Arc;
use tracing::{error, info};

/// Планировщик фоновых задач
pub struct Scheduler {
    config: AppConfig,
    iss_service: Arc<IssService>,
    osdr_service: Arc<OsdrService>,
    space_service: Arc<SpaceService>,
    // Mutex для предотвращения наложения задач
    iss_lock: Arc<Mutex<()>>,
    osdr_lock: Arc<Mutex<()>>,
    apod_lock: Arc<Mutex<()>>,
    neo_lock: Arc<Mutex<()>>,
    donki_lock: Arc<Mutex<()>>,
    spacex_lock: Arc<Mutex<()>>,
}

impl Scheduler {
    pub fn new(
        config: AppConfig,
        iss_service: Arc<IssService>,
        osdr_service: Arc<OsdrService>,
        space_service: Arc<SpaceService>,
    ) -> Self {
        Self {
            config,
            iss_service,
            osdr_service,
            space_service,
            iss_lock: Arc::new(Mutex::new(())),
            osdr_lock: Arc::new(Mutex::new(())),
            apod_lock: Arc::new(Mutex::new(())),
            neo_lock: Arc::new(Mutex::new(())),
            donki_lock: Arc::new(Mutex::new(())),
            spacex_lock: Arc::new(Mutex::new(())),
        }
    }

    /// Запустить все фоновые задачи
    pub fn start_all(&self) {
        self.start_iss_fetcher();
        self.start_osdr_fetcher();
        self.start_apod_fetcher();
        self.start_neo_fetcher();
        self.start_donki_fetcher();
        self.start_spacex_fetcher();
    }

    fn start_iss_fetcher(&self) {
        let service = Arc::clone(&self.iss_service);
        let lock = Arc::clone(&self.iss_lock);
        let interval = self.config.fetch_every_iss;

        tokio::spawn(async move {
            info!("ISS fetcher started with interval: {}s", interval);
            loop {
                // Защита от наложения
                let _guard = lock.lock().await;
                
                if let Err(e) = service.fetch_and_store().await {
                    error!("ISS fetch error: {:?}", e);
                }
                
                drop(_guard);
                tokio::time::sleep(Duration::from_secs(interval)).await;
            }
        });
    }

    fn start_osdr_fetcher(&self) {
        let service = Arc::clone(&self.osdr_service);
        let lock = Arc::clone(&self.osdr_lock);
        let interval = self.config.fetch_every_osdr;

        tokio::spawn(async move {
            info!("OSDR fetcher started with interval: {}s", interval);
            loop {
                let _guard = lock.lock().await;
                
                match service.sync().await {
                    Ok(count) => info!("OSDR synced: {} items", count),
                    Err(e) => error!("OSDR sync error: {:?}", e),
                }
                
                drop(_guard);
                tokio::time::sleep(Duration::from_secs(interval)).await;
            }
        });
    }

    fn start_apod_fetcher(&self) {
        let service = Arc::clone(&self.space_service);
        let lock = Arc::clone(&self.apod_lock);
        let interval = self.config.fetch_every_apod;

        tokio::spawn(async move {
            info!("APOD fetcher started with interval: {}s", interval);
            loop {
                let _guard = lock.lock().await;
                
                if let Err(e) = service.refresh_apod().await {
                    error!("APOD fetch error: {:?}", e);
                }
                
                drop(_guard);
                tokio::time::sleep(Duration::from_secs(interval)).await;
            }
        });
    }

    fn start_neo_fetcher(&self) {
        let service = Arc::clone(&self.space_service);
        let lock = Arc::clone(&self.neo_lock);
        let interval = self.config.fetch_every_neo;

        tokio::spawn(async move {
            info!("NEO fetcher started with interval: {}s", interval);
            loop {
                let _guard = lock.lock().await;
                
                if let Err(e) = service.refresh_neo().await {
                    error!("NEO fetch error: {:?}", e);
                }
                
                drop(_guard);
                tokio::time::sleep(Duration::from_secs(interval)).await;
            }
        });
    }

    fn start_donki_fetcher(&self) {
        let service = Arc::clone(&self.space_service);
        let lock = Arc::clone(&self.donki_lock);
        let interval = self.config.fetch_every_donki;

        tokio::spawn(async move {
            info!("DONKI fetcher started with interval: {}s", interval);
            loop {
                let _guard = lock.lock().await;
                
                let _ = service.refresh_donki_flares().await;
                let _ = service.refresh_donki_cme().await;
                
                drop(_guard);
                tokio::time::sleep(Duration::from_secs(interval)).await;
            }
        });
    }

    fn start_spacex_fetcher(&self) {
        let service = Arc::clone(&self.space_service);
        let lock = Arc::clone(&self.spacex_lock);
        let interval = self.config.fetch_every_spacex;

        tokio::spawn(async move {
            info!("SpaceX fetcher started with interval: {}s", interval);
            loop {
                let _guard = lock.lock().await;
                
                if let Err(e) = service.refresh_spacex().await {
                    error!("SpaceX fetch error: {:?}", e);
                }
                
                drop(_guard);
                tokio::time::sleep(Duration::from_secs(interval)).await;
            }
        });
    }
}
