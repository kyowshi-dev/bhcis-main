<?php

namespace App\Services;

use Asantibanez\LivewireCharts\Models\LineChartModel;
use Asantibanez\LivewireCharts\Models\PieChartModel;
use Carbon\Carbon;
use Illuminate\Support\Collection;

final class DashboardChartsService
{
    /**
     * @param  Collection<int, object{day: string, total: int}>  $rows
     */
    public static function patientVolumeChart(Carbon $today, Collection $rows): LineChartModel
    {
        $volumeByDay = [];

        foreach ($rows as $row) {
            $volumeByDay[Carbon::parse($row->day)->toDateString()] = (int) $row->total;
        }

        $chart = (new LineChartModel)
            ->setTitle('Patient volume')
            ->singleLine()
            ->withLegend()
            ->setDataLabelsEnabled(true)
            ->setAnimated(false)
            // Mid-tone green reads on both light and dark cards (first visit
            // before the theme cookie exists); ApexCharts axes follow theme mode.
            ->setColors(['#15803d'])
            ->setThemeMode(self::chartThemeMode());

        for ($daysAgo = 6; $daysAgo >= 0; $daysAgo--) {
            $date = $today->copy()->subDays($daysAgo);
            $chart->addPoint($date->format('D'), $volumeByDay[$date->toDateString()] ?? 0);
        }

        return $chart;
    }

    /**
     * @param  Collection<int, object{name: string, total: int}>  $rows
     */
    public static function presentingIllnessesChart(Collection $rows): PieChartModel
    {
        $colors = ['#15803d', '#f97316', '#ec4899', '#22c55e', '#3b82f6', '#f59e0b'];

        $chart = (new PieChartModel)
            ->setTitle('Top presenting illnesses')
            ->asDonut()
            ->withoutLegend()
            ->setDataLabelsEnabled(false)
            ->setColors($colors)
            ->setThemeMode(self::chartThemeMode());

        if ($rows->isEmpty()) {
            $chart->addSlice('No diagnoses', 1, '#94a3b8');

            return $chart;
        }

        foreach ($rows->values() as $index => $illness) {
            $chart->addSlice(
                $illness->name,
                (int) $illness->total,
                $colors[$index % count($colors)]
            );
        }

        return $chart;
    }

    /**
     * Resolved theme from the unencrypted cookie written by the app JS
     * ("light" or "dark"; preference "system" is resolved client-side).
     */
    private static function chartThemeMode(): string
    {
        return ($_COOKIE['bhcis-theme'] ?? null) === 'dark' ? 'dark' : 'light';
    }
}
