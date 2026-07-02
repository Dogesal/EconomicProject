<?php

namespace App\Application\Recurring;

use App\Application\Transactions\RecordTransaction;
use App\Domain\Models\RecurringTransaction;
use Carbon\CarbonInterface;
use Illuminate\Support\Carbon;

/**
 * Materialises all recurring transactions whose next run date has arrived.
 * Because the app has no always-on scheduler, this runs on app open and
 * catches up every occurrence missed since the last launch.
 */
class GenerateDueRecurringTransactions
{
    public function __construct(private RecordTransaction $record) {}

    public function handle(?CarbonInterface $asOf = null): int
    {
        $today = ($asOf ?? Carbon::today())->startOfDay();
        $generated = 0;

        $due = RecurringTransaction::query()
            ->with('account', 'category')
            ->whereDate('next_run_on', '<=', $today->toDateString())
            ->get();

        foreach ($due as $recurring) {
            $runDate = $recurring->next_run_on->copy();

            while (
                $runDate->lessThanOrEqualTo($today)
                && ($recurring->end_on === null || $runDate->lessThanOrEqualTo($recurring->end_on))
            ) {
                $this->record->handle(
                    account: $recurring->account,
                    type: $recurring->type,
                    amount: $recurring->amount,
                    category: $recurring->category,
                    description: $recurring->description,
                    occurredOn: $runDate,
                );

                $generated++;
                $runDate = $recurring->frequency->advance($runDate, $recurring->interval);
            }

            $recurring->update(['next_run_on' => $runDate]);
        }

        return $generated;
    }
}
