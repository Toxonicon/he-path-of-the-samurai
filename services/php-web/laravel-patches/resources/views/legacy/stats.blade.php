@extends('layouts.app')

@section('title', 'Pascal Legacy Statistics')

@section('content')
<div class="container-fluid mt-4">
    <div class="row mb-4 fade-in-up">
        <div class="col-12">
            <div class="glass-card p-4">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h2 class="text-gradient mb-0">
                        <i class="bi bi-graph-up"></i> Sensor Statistics
                    </h2>
                    <a href="{{ url('/legacy') }}" class="btn btn-cosmic">
                        <i class="bi bi-table"></i> View Data
                    </a>
                </div>
                <p class="text-light-muted">
                    Aggregated statistics from Pascal Legacy telemetry data
                </p>
            </div>
        </div>
    </div>

    <div class="row">
        @foreach($stats as $stat)
        <div class="col-md-6 col-lg-4 mb-4 fade-in" style="animation-delay: {{ $loop->index * 0.1 }}s">
            <div class="glass-card p-4 hover-lift">
                <h5 class="text-gradient mb-3">
                    <i class="bi bi-cpu"></i> {{ $stat->sensor_name }}
                </h5>

                <div class="row g-3">
                    <div class="col-6">
                        <div class="stat-item">
                            <div class="stat-label text-muted">Total Records</div>
                            <div class="stat-value text-white">{{ number_format($stat->total_records) }}</div>
                        </div>
                    </div>
                    <div class="col-6">
                        <div class="stat-item">
                            <div class="stat-label text-muted">Avg Voltage</div>
                            <div class="stat-value text-warning">{{ number_format($stat->avg_voltage, 2) }} V</div>
                        </div>
                    </div>
                    <div class="col-6">
                        <div class="stat-item">
                            <div class="stat-label text-muted">Avg Temp</div>
                            <div class="stat-value text-{{ $stat->avg_temperature > 50 ? 'danger' : 'success' }}">
                                {{ number_format($stat->avg_temperature, 2) }} °C
                            </div>
                        </div>
                    </div>
                    <div class="col-6">
                        <div class="stat-item">
                            <div class="stat-label text-muted">Avg Pressure</div>
                            <div class="stat-value text-primary">{{ number_format($stat->avg_pressure, 2) }} hPa</div>
                        </div>
                    </div>
                    <div class="col-12">
                        <div class="progress mb-2" style="height: 25px;">
                            <div class="progress-bar bg-success" role="progressbar" 
                                 style="width: {{ $stat->online_percentage }}%" 
                                 aria-valuenow="{{ $stat->online_percentage }}" 
                                 aria-valuemin="0" aria-valuemax="100">
                                Online: {{ number_format($stat->online_percentage, 1) }}%
                            </div>
                        </div>
                        <div class="progress" style="height: 25px;">
                            <div class="progress-bar bg-warning" role="progressbar" 
                                 style="width: {{ $stat->calibrated_percentage }}%" 
                                 aria-valuenow="{{ $stat->calibrated_percentage }}" 
                                 aria-valuemin="0" aria-valuemax="100">
                                Calibrated: {{ number_format($stat->calibrated_percentage, 1) }}%
                            </div>
                        </div>
                    </div>
                    <div class="col-12">
                        <div class="stat-item mt-2">
                            <div class="stat-label text-muted">First Seen</div>
                            <div class="stat-value text-info small">
                                {{ \Carbon\Carbon::parse($stat->first_seen)->format('Y-m-d H:i') }}
                            </div>
                        </div>
                        <div class="stat-item mt-1">
                            <div class="stat-label text-muted">Last Seen</div>
                            <div class="stat-value text-info small">
                                {{ \Carbon\Carbon::parse($stat->last_seen)->format('Y-m-d H:i') }}
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        @endforeach
    </div>
</div>

<style>
.stat-item {
    margin-bottom: 0.5rem;
}
.stat-label {
    font-size: 0.85rem;
    font-weight: 500;
}
.stat-value {
    font-size: 1.1rem;
    font-weight: 600;
}
</style>
@endsection
