<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\NumberFormat;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Border;

class LegacyController extends Controller
{
    /**
     * Отображает список телеметрии из Pascal Legacy CSV
     */
    public function index()
    {
        $telemetry = DB::table('telemetry_legacy')
            ->orderBy('timestamp', 'desc')
            ->limit(1000)
            ->get();

        return view('legacy.index', compact('telemetry'));
    }

    /**
     * API endpoint для получения телеметрии в JSON
     */
    public function api()
    {
        $telemetry = DB::table('telemetry_legacy')
            ->orderBy('timestamp', 'desc')
            ->limit(1000)
            ->get();

        return response()->json($telemetry);
    }

    /**
     * Статистика по сенсорам
     */
    public function stats()
    {
        $stats = DB::select("
            SELECT 
                sensor_name,
                COUNT(*) as total_records,
                AVG(voltage) as avg_voltage,
                AVG(temperature) as avg_temperature,
                AVG(pressure) as avg_pressure,
                SUM(CASE WHEN is_online THEN 1 ELSE 0 END)::float / COUNT(*) * 100 as online_percentage,
                SUM(CASE WHEN is_calibrated THEN 1 ELSE 0 END)::float / COUNT(*) * 100 as calibrated_percentage,
                MIN(timestamp) as first_seen,
                MAX(timestamp) as last_seen
            FROM telemetry_legacy
            GROUP BY sensor_name
            ORDER BY sensor_name
        ");

        return view('legacy.stats', compact('stats'));
    }

    /**
     * Экспорт телеметрии в XLSX с форматированием
     */
    public function exportXlsx()
    {
        $telemetry = DB::table('telemetry_legacy')
            ->orderBy('timestamp', 'desc')
            ->get();

        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        
        // Заголовок
        $sheet->setTitle('Legacy Telemetry');
        
        // Заголовки столбцов
        $headers = ['ID', 'Timestamp', 'Sensor Name', 'Voltage', 'Temperature', 'Pressure', 'Online', 'Calibrated', 'Status', 'Source File'];
        $sheet->fromArray($headers, null, 'A1');
        
        // Стиль заголовков
        $headerStyle = [
            'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '4472C4']],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
            'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN]]
        ];
        $sheet->getStyle('A1:J1')->applyFromArray($headerStyle);
        
        // Данные
        $row = 2;
        foreach ($telemetry as $record) {
            $sheet->setCellValue('A' . $row, $record->id);
            $sheet->setCellValue('B' . $row, $record->timestamp);
            $sheet->setCellValue('C' . $row, $record->sensor_name);
            $sheet->setCellValue('D' . $row, $record->voltage);
            $sheet->setCellValue('E' . $row, $record->temperature);
            $sheet->setCellValue('F' . $row, $record->pressure);
            $sheet->setCellValue('G' . $row, $record->is_online ? 'TRUE' : 'FALSE');
            $sheet->setCellValue('H' . $row, $record->is_calibrated ? 'TRUE' : 'FALSE');
            $sheet->setCellValue('I' . $row, $record->status);
            $sheet->setCellValue('J' . $row, $record->source_file);
            
            $row++;
        }
        
        // Форматирование столбцов
        $sheet->getStyle('B2:B' . ($row - 1))->getNumberFormat()->setFormatCode('yyyy-mm-dd hh:mm:ss');
        $sheet->getStyle('D2:D' . ($row - 1))->getNumberFormat()->setFormatCode(NumberFormat::FORMAT_NUMBER_00);
        $sheet->getStyle('E2:E' . ($row - 1))->getNumberFormat()->setFormatCode(NumberFormat::FORMAT_NUMBER_00);
        $sheet->getStyle('F2:F' . ($row - 1))->getNumberFormat()->setFormatCode(NumberFormat::FORMAT_NUMBER_00);
        
        // Автоширина столбцов
        foreach (range('A', 'J') as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }
        
        // Генерация файла
        $filename = 'legacy_telemetry_' . date('Y-m-d_His') . '.xlsx';
        $writer = new Xlsx($spreadsheet);
        
        // Отправка файла
        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment;filename="' . $filename . '"');
        header('Cache-Control: max-age=0');
        
        $writer->save('php://output');
        exit;
    }
}
