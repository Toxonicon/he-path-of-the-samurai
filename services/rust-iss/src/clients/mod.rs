// Clients layer - работа с внешними API
pub mod iss_client;
pub mod nasa_client;
pub mod spacex_client;
pub mod base_client;

pub use iss_client::IssClient;
pub use nasa_client::NasaClient;
pub use spacex_client::SpacexClient;
pub use base_client::BaseClient;
