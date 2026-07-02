<?php

namespace App\Support;

use App\Domain\Enums\DebtStatus;
use App\Domain\Models\Debt;
use App\Domain\Models\RecurringTransaction;
use Carbon\CarbonInterface;
use Ikromjon\LocalNotifications\LocalNotifications;

/**
 * Builds and (re)schedules the on-device reminders: debts close to their due
 * date and recurring transactions about to run. Re-synced on every app open
 * (cancelAll + schedule) so the pending list always mirrors the data. The
 * native calls no-op outside the device runtime.
 */
class ReminderScheduler
{
    private const DEBT_WINDOW_DAYS = 7;

    private const RECURRING_WINDOW_DAYS = 2;

    public function __construct(private LocalNotifications $notifications) {}

    public function schedule(): void
    {
        if (! function_exists('nativephp_call')) {
            return;
        }

        $this->notifications->requestPermission();
        $this->notifications->cancelAll();

        foreach ($this->upcoming() as $reminder) {
            $this->notifications->schedule($reminder);
        }
    }

    /**
     * Pure reminder selection, separated from the native calls so it can be
     * tested without a device.
     *
     * @return list<array{id: string, title: string, body: string, at: int}>
     */
    public function upcoming(): array
    {
        $reminders = [];
        $now = now();

        Debt::query()
            ->where('status', DebtStatus::Active)
            ->whereNotNull('due_date')
            ->whereDate('due_date', '<=', $now->copy()->addDays(self::DEBT_WINDOW_DAYS))
            ->get()
            ->each(function (Debt $debt) use (&$reminders, $now) {
                $overdue = $debt->isOverdue();

                $reminders[] = [
                    'id' => 'debt-'.$debt->id,
                    'title' => $overdue ? 'Deuda vencida' : 'Deuda por vencer',
                    'body' => sprintf(
                        '«%s» %s el %s — restan %s.',
                        $debt->name,
                        $overdue ? 'venció' : 'vence',
                        $debt->due_date->format('d/m'),
                        $debt->remaining()->format(),
                    ),
                    'at' => $this->fireAt($debt->due_date, $now),
                ];
            });

        RecurringTransaction::query()
            ->whereDate('next_run_on', '<=', $now->copy()->addDays(self::RECURRING_WINDOW_DAYS))
            ->when(true, fn ($q) => $q->where(fn ($q) => $q->whereNull('end_on')->orWhereDate('end_on', '>=', $now)))
            ->get()
            ->each(function (RecurringTransaction $recurring) use (&$reminders, $now) {
                $reminders[] = [
                    'id' => 'recurring-'.$recurring->id,
                    'title' => 'Pago recurrente próximo',
                    'body' => sprintf(
                        '«%s» (%s) se registra el %s.',
                        $recurring->description ?? $recurring->type->label(),
                        $recurring->amount->format(),
                        $recurring->next_run_on->format('d/m'),
                    ),
                    'at' => $this->fireAt($recurring->next_run_on, $now),
                ];
            });

        return $reminders;
    }

    /**
     * Notify at 09:00 of the relevant day, or in one minute when that moment
     * already passed (overdue items still get a heads-up).
     */
    private function fireAt(CarbonInterface $date, CarbonInterface $now): int
    {
        return max(
            $date->copy()->setTime(9, 0)->getTimestamp(),
            $now->copy()->addMinute()->getTimestamp(),
        );
    }
}
