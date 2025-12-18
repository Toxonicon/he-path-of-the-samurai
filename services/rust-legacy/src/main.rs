mod telemetry;

use anyhow::Result;
use chrono::Utc;
use clap::{Parser, Subcommand};
use sqlx::postgres::PgPoolOptions;
use std::path::PathBuf;
use std::time::Duration;
use telemetry::{CsvGenerator, TelemetryRecord, XlsxGenerator};
use tracing::{error, info};
use tracing_subscriber::{EnvFilter, FmtSubscriber};

#[derive(Parser)]
#[command(name = "legacy-telemetry")]
#[command(about = "Telemetry data generator (Legacy replacement)", long_about = None)]
struct Cli {
    #[command(subcommand)]
    command: Commands,
}

#[derive(Subcommand)]
enum Commands {
    /// Сгенерировать CSV файл
    GenerateCsv {
        /// Выходная директория
        #[arg(short, long, default_value = "/data/csv")]
        output_dir: PathBuf,

        /// Количество записей
        #[arg(short, long, default_value = "10")]
        count: usize,
    },

    /// Сгенерировать XLSX файл
    GenerateXlsx {
        /// Выходная директория
        #[arg(short, long, default_value = "/data/csv")]
        output_dir: PathBuf,

        /// Количество записей
        #[arg(short, long, default_value = "10")]
        count: usize,
    },

    /// Запустить в режиме демона (периодическая генерация)
    Daemon {
        /// Период генерации в секундах
        #[arg(short, long, default_value = "300")]
        period: u64,

        /// Формат: csv, xlsx, both
        #[arg(short, long, default_value = "both")]
        format: String,
    },
}

#[tokio::main]
async fn main() -> Result<()> {
    // Инициализация логирования
    let subscriber = FmtSubscriber::builder()
        .with_env_filter(EnvFilter::from_default_env())
        .finish();
    tracing::subscriber::set_global_default(subscriber)?;

    info!("🚀 Legacy Telemetry Generator v{}", env!("CARGO_PKG_VERSION"));

    dotenvy::dotenv().ok();

    let cli = Cli::parse();

    match cli.command {
        Commands::GenerateCsv { output_dir, count } => {
            generate_csv(&output_dir, count).await?;
        }
        Commands::GenerateXlsx { output_dir, count } => {
            generate_xlsx(&output_dir, count).await?;
        }
        Commands::Daemon { period, format } => {
            run_daemon(period, &format).await?;
        }
    }

    Ok(())
}

async fn generate_csv(output_dir: &PathBuf, count: usize) -> Result<()> {
    std::fs::create_dir_all(output_dir)?;

    let timestamp = Utc::now().format("%Y%m%d_%H%M%S");
    let filename = format!("telemetry_{}.csv", timestamp);
    let filepath = output_dir.join(&filename);

    info!("📝 Generating CSV: {}", filepath.display());

    let records: Vec<TelemetryRecord> = (0..count)
        .map(|_| TelemetryRecord::generate_random(filename.clone()))
        .collect();

    CsvGenerator::generate(&filepath, &records)?;

    // Запись в PostgreSQL
    if let Err(e) = insert_to_database(&records).await {
        error!("Failed to insert into database: {}", e);
    }

    info!("✅ CSV generated successfully: {} records", count);
    Ok(())
}

async fn generate_xlsx(output_dir: &PathBuf, count: usize) -> Result<()> {
    std::fs::create_dir_all(output_dir)?;

    let timestamp = Utc::now().format("%Y%m%d_%H%M%S");
    let filename = format!("telemetry_{}.xlsx", timestamp);
    let filepath = output_dir.join(&filename);

    info!("📊 Generating XLSX: {}", filepath.display());

    let records: Vec<TelemetryRecord> = (0..count)
        .map(|_| TelemetryRecord::generate_random(filename.clone()))
        .collect();

    XlsxGenerator::generate(&filepath, &records)?;

    // Запись в PostgreSQL
    if let Err(e) = insert_to_database(&records).await {
        error!("Failed to insert into database: {}", e);
    }

    info!("✅ XLSX generated successfully: {} records", count);
    Ok(())
}

async fn run_daemon(period: u64, format: &str) -> Result<()> {
    info!("⏰ Starting daemon mode (period: {}s, format: {})", period, format);

    let output_dir = std::env::var("CSV_OUT_DIR")
        .unwrap_or_else(|_| "/data/csv".to_string())
        .into();

    loop {
        match format {
            "csv" => {
                if let Err(e) = generate_csv(&output_dir, 5).await {
                    error!("CSV generation failed: {}", e);
                }
            }
            "xlsx" => {
                if let Err(e) = generate_xlsx(&output_dir, 5).await {
                    error!("XLSX generation failed: {}", e);
                }
            }
            "both" => {
                if let Err(e) = generate_csv(&output_dir, 5).await {
                    error!("CSV generation failed: {}", e);
                }
                if let Err(e) = generate_xlsx(&output_dir, 5).await {
                    error!("XLSX generation failed: {}", e);
                }
            }
            _ => {
                error!("Unknown format: {}", format);
            }
        }

        tokio::time::sleep(Duration::from_secs(period)).await;
    }
}

async fn insert_to_database(records: &[TelemetryRecord]) -> Result<()> {
    let db_url = std::env::var("DATABASE_URL")?;
    let pool = PgPoolOptions::new()
        .max_connections(2)
        .connect(&db_url)
        .await?;

    for record in records {
        sqlx::query(
            "INSERT INTO telemetry_legacy(recorded_at, voltage, temp, source_file)
             VALUES ($1, $2, $3, $4)"
        )
        .bind(record.recorded_at)
        .bind(record.voltage)
        .bind(record.temp)
        .bind(&record.source_file)
        .execute(&pool)
        .await?;
    }

    info!("💾 Inserted {} records into database", records.len());
    Ok(())
}
