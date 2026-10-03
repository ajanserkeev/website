<?php

namespace App\Filament\Partner\Widgets;

use App\Filament\Partner\Widgets\Concerns\ScopesSales;
use Filament\Widgets\ChartWidget;
use Illuminate\Support\Carbon;

class SalesByMonth extends ChartWidget
{
    use ScopesSales;

    protected static bool $isLazy = false;

    protected static ?int $sort = 2;

    protected int|string|array $columnSpan = 'full';

    protected ?string $heading = 'По месяцам';

    protected ?string $maxHeight = '280px';

    protected function getType(): string
    {
        return 'bar';
    }

    protected function getData(): array
    {
        $months = $this->stats()->byMonth();
        $dollars = fn (string $key) => array_map(fn ($m) => round($m[$key] / 100), array_values($months));

        return [
            'datasets' => [
                ['label' => $this->operatorId() === null ? 'Турфирмам, $' : 'Вам, $', 'data' => $dollars('operator_cents'), 'backgroundColor' => '#11804A', 'stack' => 'money'],
                ['label' => 'Комиссия площадки, $', 'data' => $dollars('commission_cents'), 'backgroundColor' => '#E7A500', 'stack' => 'money'],
                ['label' => 'Туристов', 'data' => array_column(array_values($months), 'travelers'), 'type' => 'line', 'borderColor' => '#2D5D8C', 'yAxisID' => 'people'],
            ],
            'labels' => array_map(fn ($key) => Carbon::parse("{$key}-01")->locale('ru')->translatedFormat('M Y'), array_keys($months)),
        ];
    }

    protected function getOptions(): array
    {
        return [
            'scales' => [
                'x' => ['stacked' => true],
                'y' => ['stacked' => true, 'beginAtZero' => true],
                'people' => ['position' => 'right', 'beginAtZero' => true, 'grid' => ['drawOnChartArea' => false]],
            ],
        ];
    }
}
