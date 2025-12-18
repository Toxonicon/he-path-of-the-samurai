program LegacyCSV;

{$mode objfpc}{$H+}

uses
  SysUtils, DateUtils, BaseUnix;

function GetEnvDef(const name, def: string): string;
var v: string;
begin
  v := GetEnvironmentVariable(name);
  if v = '' then Exit(def) else Exit(v);
end;

function RandFloat(minV, maxV: Double): Double;
begin
  Result := minV + Random * (maxV - minV);
end;

function RandInt(minV, maxV: Integer): Integer;
begin
  Result := minV + Random(maxV - minV + 1);
end;

function RandBool(): Boolean;
begin
  Result := Random(2) = 1;
end;

function BoolToStr(b: Boolean): string;
begin
  if b then Exit('TRUE') else Exit('FALSE');
end;

// CSV-экранирование строк
function EscapeCSV(const s: string): string;
begin
  if (Pos(',', s) > 0) or (Pos('"', s) > 0) or (Pos(#10, s) > 0) or (Pos(#13, s) > 0) then
  begin
    // Экранируем кавычки и оборачиваем в кавычки
    Result := '"' + StringReplace(s, '"', '""', [rfReplaceAll]) + '"';
  end
  else
    Result := s;
end;

// Генерирует CSV файл и возвращает путь
function GenerateCSV(): string;
var
  outDir, fn, fullpath: string;
  f: TextFile;
  ts, timestamp: string;
  i, numRows: Integer;
  voltage, temp, pressure: Double;
  isOnline, isCalibrated: Boolean;
  status, sensorName: string;
begin
  outDir := GetEnvDef('CSV_OUT_DIR', '/data/csv');
  ts := FormatDateTime('yyyymmdd_hhnnss', Now);
  fn := 'telemetry_' + ts + '.csv';
  fullpath := IncludeTrailingPathDelimiter(outDir) + fn;

  WriteLn('[Pascal Legacy] Generating CSV: ', fn);

  // Генерируем несколько строк данных (5-15)
  numRows := RandInt(5, 15);

  // Открываем файл для записи
  AssignFile(f, fullpath);
  Rewrite(f);
  
  // CSV заголовок с правильными типами
  Writeln(f, 'timestamp,sensor_name,voltage,temperature,pressure,is_online,is_calibrated,status,source_file');
  
  // Генерируем строки данных
  for i := 1 to numRows do
  begin
    // 1. TIMESTAMP - ISO 8601 format (YYYY-MM-DD HH:MM:SS)
    timestamp := FormatDateTime('yyyy-mm-dd hh:nn:ss', IncMinute(Now, -RandInt(0, 1440)));
    
    // 2. СТРОКА - имя сенсора
    case RandInt(1, 5) of
      1: sensorName := 'TEMP-SENSOR-001';
      2: sensorName := 'VOLTAGE-MON-A1';
      3: sensorName := 'PRESSURE-GAUGE-3';
      4: sensorName := 'CALIBRATION-UNIT';
      else sensorName := 'GENERIC-SENSOR';
    end;
    
    // 3. ЧИСЛА - с плавающей точкой (формат 0.00)
    voltage := RandFloat(3.2, 12.6);
    temp := RandFloat(-50.0, 80.0);
    pressure := RandFloat(900.0, 1100.0);
    
    // 4. БУЛЕВЫ - TRUE/FALSE
    isOnline := RandBool();
    isCalibrated := RandBool();
    
    // 5. СТРОКА - статус (может содержать запятые)
    if isOnline then
    begin
      if isCalibrated then
        status := 'Operational, Ready'
      else
        status := 'Online, Calibration needed'
    end
    else
      status := 'Offline';
    
    // Записываем строку CSV
    Writeln(f, 
      timestamp, ',',
      EscapeCSV(sensorName), ',',
      FormatFloat('0.00', voltage), ',',
      FormatFloat('0.00', temp), ',',
      FormatFloat('0.00', pressure), ',',
      BoolToStr(isOnline), ',',
      BoolToStr(isCalibrated), ',',
      EscapeCSV(status), ',',
      EscapeCSV(fn)
    );
  end;
  
  CloseFile(f);
  WriteLn('[Pascal Legacy] Generated ', numRows, ' rows in ', fullpath);
  Result := fullpath;
end;

// Импортирует CSV в PostgreSQL
procedure ImportToPostgres(const csvPath: string);
var
  pghost, pgport, pguser, pgdb: string;
  script: TextFile;
begin
  pghost := GetEnvDef('PGHOST', 'db');
  pgport := GetEnvDef('PGPORT', '5432');
  pguser := GetEnvDef('PGUSER', 'monouser');
  pgdb   := GetEnvDef('PGDATABASE', 'monolith');
  
  // Создаём скрипт импорта (PGPASSWORD уже установлен через environment)
  AssignFile(script, '/tmp/do_import.sh');
  Rewrite(script);
  WriteLn(script, '#!/bin/sh');
  WriteLn(script, 'psql -h ', pghost, ' -p ', pgport, ' -U ', pguser, ' -d ', pgdb, 
          ' -c "\copy telemetry_legacy(timestamp, sensor_name, voltage, temperature, pressure, is_online, is_calibrated, status, source_file) ',
          'FROM ''', csvPath, ''' WITH (FORMAT csv, HEADER true)" 2>&1');
  CloseFile(script);
  
  WriteLn('[Pascal Legacy] Importing to PostgreSQL...');
  WriteLn('[Pascal Legacy] Import script created at /tmp/do_import.sh');
end;

var 
  period: Integer;
  iteration: Integer;
  csvPath: string;
begin
  Randomize;
  period := StrToIntDef(GetEnvDef('GEN_PERIOD_SEC', '300'), 300);
  iteration := 0;
  
  WriteLn('========================================');
  WriteLn('Pascal Legacy CSV Generator');
  WriteLn('========================================');
  WriteLn('Period: ', period, ' seconds');
  WriteLn('Output directory: ', GetEnvDef('CSV_OUT_DIR', '/data/csv'));
  WriteLn('========================================');
  
  while True do
  begin
    Inc(iteration);
    WriteLn('');
    WriteLn('[Iteration #', iteration, '] ', FormatDateTime('yyyy-mm-dd hh:nn:ss', Now));
    
    try
      csvPath := GenerateCSV();
      ImportToPostgres(csvPath);
    except
      on E: Exception do
        WriteLn('[ERROR] ', E.Message);
    end;
    
    WriteLn('[Pascal Legacy] Sleeping for ', period, ' seconds...');
    Sleep(period * 1000);
  end;
end.
