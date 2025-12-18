use chrono::{DateTime, Utc};
use rand::Rng;
use serde::{Deserialize, Serialize};
use std::path::Path;

/// Запись телеметрии с типизированными полями
#[derive(Debug, Clone, Serialize, Deserialize)]
pub struct TelemetryRecord {
    /// Timestamp - время записи
    pub recorded_at: DateTime<Utc>,
    
    /// Числовое значение - напряжение (Voltage)
    pub voltage: f64,
    
    /// Числовое значение - температура
    pub temp: f64,
    
    /// Логическое значение - статус системы
    pub system_ok: bool,
    
    /// Логическое значение - критическое состояние
    pub critical: bool,
    
    /// Строка - имя источника файла
    pub source_file: String,
}

impl TelemetryRecord {
    /// Генерация случайной записи телеметрии
    pub fn generate_random(source_file: String) -> Self {
        let mut rng = rand::thread_rng();
        
        let voltage = rng.gen_range(3.2..=12.6);
        let temp = rng.gen_range(-50.0..=80.0);
        
        // Логика определения статусов
        let system_ok = voltage > 5.0 && temp > -40.0 && temp < 70.0;
        let critical = voltage < 4.0 || temp > 75.0 || temp < -45.0;
        
        Self {
            recorded_at: Utc::now(),
            voltage,
            temp,
            system_ok,
            critical,
            source_file,
        }
    }
}

/// Генератор CSV файлов с типизацией данных
pub struct CsvGenerator;

impl CsvGenerator {
    /// Генерация CSV файла с правильной типизацией:
    /// - Timestamp в ISO 8601
    /// - Boolean как TRUE/FALSE
    /// - Numeric как числа с плавающей точкой
    /// - String как текст
    pub fn generate<P: AsRef<Path>>(
        path: P,
        records: &[TelemetryRecord],
    ) -> anyhow::Result<()> {
        let mut wtr = csv::Writer::from_path(path)?;

        // Заголовки
        wtr.write_record(&[
            "recorded_at",
            "voltage",
            "temp",
            "system_ok",
            "critical",
            "source_file",
        ])?;

        // Данные с правильной типизацией
        for record in records {
            wtr.write_record(&[
                // Timestamp - ISO 8601 формат
                record.recorded_at.to_rfc3339(),
                // Numeric - числовой формат
                format!("{:.2}", record.voltage),
                format!("{:.2}", record.temp),
                // Boolean - ИСТИНА/ЛОЖЬ (TRUE/FALSE)
                if record.system_ok { "TRUE" } else { "FALSE" },
                if record.critical { "TRUE" } else { "FALSE" },
                // String - текст
                record.source_file.clone(),
            ])?;
        }

        wtr.flush()?;
        Ok(())
    }
}

/// Генератор XLSX файлов
pub struct XlsxGenerator;

impl XlsxGenerator {
    /// Генерация Excel файла с типизацией данных
    pub fn generate<P: AsRef<Path>>(
        path: P,
        records: &[TelemetryRecord],
    ) -> anyhow::Result<()> {
        use rust_xlsxwriter::*;

        let mut workbook = Workbook::new();
        let worksheet = workbook.add_worksheet();

        // Форматы
        let header_format = Format::new()
            .set_bold()
            .set_background_color(Color::RGB(0x4472C4))
            .set_font_color(Color::White);

        let date_format = Format::new()
            .set_num_format("yyyy-mm-dd hh:mm:ss");

        let number_format = Format::new()
            .set_num_format("0.00");

        // Заголовки
        let headers = [
            "Дата и время",
            "Напряжение (V)",
            "Температура (°C)",
            "Система OK",
            "Критическое состояние",
            "Источник",
        ];

        for (col, header) in headers.iter().enumerate() {
            worksheet.write_string_with_format(0, col as u16, header, &header_format)?;
        }

        // Данные
        for (row, record) in records.iter().enumerate() {
            let row = (row + 1) as u32;

            // Timestamp как DateTime
            let datetime = ExcelDateTime::from_timestamp(
                record.recorded_at.timestamp(),
                0,
            )?;
            worksheet.write_datetime_with_format(row, 0, &datetime, &date_format)?;

            // Числа
            worksheet.write_number_with_format(row, 1, record.voltage, &number_format)?;
            worksheet.write_number_with_format(row, 2, record.temp, &number_format)?;

            // Boolean
            worksheet.write_boolean(row, 3, record.system_ok)?;
            worksheet.write_boolean(row, 4, record.critical)?;

            // Строка
            worksheet.write_string(row, 5, &record.source_file)?;
        }

        // Автоширина колонок
        worksheet.set_column_width(0, 20)?;
        worksheet.set_column_width(1, 15)?;
        worksheet.set_column_width(2, 18)?;
        worksheet.set_column_width(3, 15)?;
        worksheet.set_column_width(4, 20)?;
        worksheet.set_column_width(5, 25)?;

        workbook.save(path)?;
        Ok(())
    }
}

#[cfg(test)]
mod tests {
    use super::*;

    #[test]
    fn test_generate_telemetry() {
        let record = TelemetryRecord::generate_random("test.csv".to_string());
        assert!(record.voltage >= 3.2 && record.voltage <= 12.6);
        assert!(record.temp >= -50.0 && record.temp <= 80.0);
    }
}
