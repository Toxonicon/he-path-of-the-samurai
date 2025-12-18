// Handlers layer - обработчики HTTP запросов
pub mod health;
pub mod iss_handlers;
pub mod osdr_handlers;
pub mod space_handlers;

pub use health::health_check;
