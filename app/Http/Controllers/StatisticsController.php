<?php

namespace App\Http\Controllers;

use App\Application\Reports\MonthlyEvolution;
use App\Application\Statistics\GenerateRecommendations;
use App\Application\Statistics\MonthOverview;
use App\Application\Statistics\SpendingHabits;
use App\Support\DisplayCurrency;
use Inertia\Inertia;
use Inertia\Response;

class StatisticsController extends Controller
{
    public function __invoke(
        MonthOverview $overview,
        SpendingHabits $habits,
        GenerateRecommendations $recommendations,
        MonthlyEvolution $evolution,
        DisplayCurrency $currency,
    ): Response {
        $year = (int) request()->integer('year', (int) now()->year);
        $month = (int) request()->integer('month', (int) now()->month);
        $displayCurrency = $currency->resolve();

        return Inertia::render('Statistics/Index', [
            'period' => ['year' => $year, 'month' => $month],
            'currency' => $displayCurrency,
            'overview' => $overview->handle($year, $month, $displayCurrency),
            'habits' => $habits->handle($year, $month, $displayCurrency),
            'recommendations' => $recommendations->handle($year, $month, $displayCurrency)->values(),
            'trend' => $evolution->handle($displayCurrency, 6),
        ]);
    }
}
