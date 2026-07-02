<?php

namespace App\Http\Controllers;

use App\Application\Goals\ContributeToGoal;
use App\Application\Goals\CreateGoal;
use App\Application\Goals\WithdrawFromGoal;
use App\Data\GoalData;
use App\Domain\Models\SavingsGoal;
use App\Domain\ValueObjects\Money;
use App\Http\Requests\ContributeGoalRequest;
use App\Http\Requests\StoreGoalRequest;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class GoalController extends Controller
{
    public function index(): Response
    {
        return Inertia::render('Goals/Index', [
            'goals' => GoalData::collect(SavingsGoal::orderByDesc('created_at')->get()),
        ]);
    }

    public function store(StoreGoalRequest $request, CreateGoal $create): RedirectResponse
    {
        $currency = strtoupper($request->string('currency'));

        $create->handle(
            name: $request->string('name'),
            target: Money::fromDecimal($request->input('target_amount'), $currency),
            targetDate: $request->date('target_date'),
        );

        return back()->with('success', 'Meta creada.');
    }

    public function contribute(ContributeGoalRequest $request, SavingsGoal $goal, ContributeToGoal $contribute): RedirectResponse
    {
        $contribute->handle($goal, Money::fromDecimal($request->input('amount'), $goal->currency));

        return back()->with('success', 'Aporte registrado.');
    }

    public function withdraw(ContributeGoalRequest $request, SavingsGoal $goal, WithdrawFromGoal $withdraw): RedirectResponse
    {
        $withdraw->handle($goal, Money::fromDecimal($request->input('amount'), $goal->currency));

        return back()->with('success', 'Retiro registrado.');
    }

    public function destroy(SavingsGoal $goal): RedirectResponse
    {
        $goal->delete();

        return back()->with('success', 'Meta eliminada.');
    }
}
