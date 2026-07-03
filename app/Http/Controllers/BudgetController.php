<?php

namespace App\Http\Controllers;

use App\Application\Budgets\CalculateBudgetConsumption;
use App\Application\Budgets\CreateBudget;
use App\Application\Reports\ExpensesForCategory;
use App\Data\CategoryData;
use App\Domain\Enums\CategoryType;
use App\Domain\Models\Budget;
use App\Domain\Models\Category;
use App\Domain\ValueObjects\Money;
use App\Http\Requests\StoreBudgetRequest;
use App\Support\DisplayCurrency;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class BudgetController extends Controller
{
    public function index(CalculateBudgetConsumption $consumption, ExpensesForCategory $expenses, DisplayCurrency $currency): Response
    {
        $year = (int) request()->integer('year', (int) now()->year);
        $month = (int) request()->integer('month', (int) now()->month);
        $displayCurrency = $currency->resolve();

        return Inertia::render('Budgets/Index', [
            'period' => ['year' => $year, 'month' => $month],
            'currency' => $displayCurrency,
            'consumption' => $consumption->handle($year, $month),
            'expenseCategories' => CategoryData::collect(
                Category::where('type', CategoryType::Expense)->orderBy('name')->get()
            ),
            'categoryExpenses' => Inertia::optional(fn () => request()->filled('drill_category')
                ? $expenses->handle(request()->string('drill_category')->toString(), $year, $month, $displayCurrency)
                : null),
        ]);
    }

    public function store(StoreBudgetRequest $request, CreateBudget $create, DisplayCurrency $currency): RedirectResponse
    {
        $category = Category::findOrFail($request->string('category_id'));

        $create->handle(
            category: $category,
            year: $request->integer('period_year'),
            month: $request->integer('period_month'),
            amount: Money::fromDecimal($request->input('amount'), $currency->resolve()),
        );

        return back()->with('success', 'Presupuesto guardado.');
    }

    public function destroy(Budget $budget): RedirectResponse
    {
        $budget->delete();

        return back()->with('success', 'Presupuesto eliminado.');
    }
}
