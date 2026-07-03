<?php

namespace App\Application\Statistics;

use App\Application\Budgets\CalculateBudgetConsumption;
use App\Application\Reports\SpendingByCategory;
use App\Data\RecommendationData;
use App\Domain\ValueObjects\Money;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

/**
 * Rule-based spending recommendations for a month, ordered by severity
 * (danger, warning, info) and capped at five entries.
 */
class GenerateRecommendations
{
    private const BUDGET_NEAR_THRESHOLD = 90.0;

    private const GROWTH_THRESHOLD = 30.0;

    private const GROWTH_MIN_SHARE = 5.0;

    private const SAVINGS_DOMINANCE_THRESHOLD = 25.0;

    private const SAVINGS_CUT_RATE = 0.10;

    private const MAX_RECOMMENDATIONS = 5;

    private const SEVERITY_RANK = ['danger' => 0, 'warning' => 1, 'info' => 2];

    public function __construct(
        private CalculateBudgetConsumption $consumption,
        private SpendingByCategory $spending,
    ) {}

    /**
     * @return Collection<int, RecommendationData>
     */
    public function handle(int $year, int $month, string $currency): Collection
    {
        $recommendations = collect();

        foreach ($this->consumption->handle($year, $month) as $row) {
            if ($row->isOverBudget) {
                $recommendations->push(new RecommendationData(
                    type: 'budget_over',
                    severity: 'danger',
                    title: "Presupuesto excedido: {$row->category->name}",
                    message: "Gastaste {$row->spent->formatted} de {$row->budgeted->formatted} ({$row->percentage}%).",
                    categoryId: $row->category->id,
                ));
            } elseif ($row->percentage >= self::BUDGET_NEAR_THRESHOLD) {
                $recommendations->push(new RecommendationData(
                    type: 'budget_near',
                    severity: 'warning',
                    title: "Cerca del límite: {$row->category->name}",
                    message: "Llevas el {$row->percentage}% del presupuesto ({$row->spent->formatted} de {$row->budgeted->formatted}).",
                    categoryId: $row->category->id,
                ));
            }
        }

        $currentSpending = $this->spending->handle($year, $month, $currency);
        $previousMonth = Carbon::create($year, $month, 1)->subMonth();
        $previousSpending = $this->spending
            ->handle((int) $previousMonth->year, (int) $previousMonth->month, $currency)
            ->keyBy('categoryId');

        foreach ($currentSpending as $row) {
            $previous = $previousSpending->get($row->categoryId);

            if ($previous === null || $previous->total->minorUnits <= 0) {
                continue;
            }

            $growth = (($row->total->minorUnits - $previous->total->minorUnits) / $previous->total->minorUnits) * 100;

            if ($growth >= self::GROWTH_THRESHOLD && $row->percentage >= self::GROWTH_MIN_SHARE) {
                $recommendations->push(new RecommendationData(
                    type: 'category_growth',
                    severity: 'warning',
                    title: "Gasto en aumento: {$row->categoryName}",
                    message: "Gastaste {$row->total->formatted}, un ".round($growth)."% más que el mes anterior ({$previous->total->formatted}).",
                    categoryId: $row->categoryId,
                ));
            }
        }

        $dominant = $currentSpending->first();

        if ($dominant !== null && $dominant->percentage >= self::SAVINGS_DOMINANCE_THRESHOLD) {
            $saving = Money::fromMinor((int) round($dominant->total->minorUnits * self::SAVINGS_CUT_RATE), $currency);

            $recommendations->push(new RecommendationData(
                type: 'savings',
                severity: 'info',
                title: 'Oportunidad de ahorro',
                message: "\u{201C}{$dominant->categoryName}\u{201D} representa el {$dominant->percentage}% de tus gastos. Reducirlo un 10% te ahorraría {$saving->format()} al mes.",
                categoryId: $dominant->categoryId,
            ));
        }

        return $recommendations
            ->sortBy(fn (RecommendationData $r) => self::SEVERITY_RANK[$r->severity])
            ->take(self::MAX_RECOMMENDATIONS)
            ->values();
    }
}
