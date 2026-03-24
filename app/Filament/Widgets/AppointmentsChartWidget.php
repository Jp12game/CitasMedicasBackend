<?php

namespace App\Filament\Widgets;

use App\Models\Appointment;
use Filament\Widgets\ChartWidget;
use Illuminate\Support\Carbon;

class AppointmentsChartWidget extends ChartWidget
{
    protected ?string $heading = 'Citas por Día (Últimos 7 días)';

    protected static ?int $sort = 2;

    protected function getType(): string
    {
        return 'bar';
    }

    protected function getData(): array
    {
        $days = collect(range(6, 0))->map(fn ($i) => now()->subDays($i)->toDateString());

        $counts = Appointment::selectRaw('DATE(date_time_begin) as date, COUNT(*) as total')
            ->where('date_time_begin', '>=', now()->subDays(6)->startOfDay())
            ->groupBy('date')
            ->pluck('total', 'date');

        return [
            'datasets' => [
                [
                    'label'           => 'Citas',
                    'data'            => $days->map(fn ($d) => $counts->get($d, 0))->values()->toArray(),
                    'backgroundColor' => 'rgba(245, 158, 11, 0.7)',
                    'borderColor'     => 'rgba(245, 158, 11, 1)',
                    'borderWidth'     => 1,
                ],
            ],
            'labels' => $days->map(fn ($d) => Carbon::parse($d)->translatedFormat('D d/m'))->toArray(),
        ];
    }
}
