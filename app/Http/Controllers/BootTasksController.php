<?php

namespace App\Http\Controllers;

use App\Application\Recurring\GenerateDueRecurringTransactions;
use App\Support\ReminderScheduler;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Tareas de mantenimiento que antes corrían dentro del Dashboard y
 * bloqueaban el primer render: poner al día los movimientos recurrentes y
 * re-agendar los recordatorios locales (varias llamadas al puente nativo).
 *
 * El layout lo dispara async apenas monta (POST /boot/tasks), así el
 * splash desaparece con el primer paint y esto corre después, en cualquier
 * pantalla. La recarga de props deja aparecer los recurrentes generados.
 */
class BootTasksController extends Controller
{
    public function __invoke(
        GenerateDueRecurringTransactions $recurring,
        ReminderScheduler $reminders,
    ): RedirectResponse {
        try {
            $recurring->handle();
        } catch (Throwable $e) {
            Log::warning('Recurring catch-up failed: '.$e->getMessage());
        }

        try {
            $reminders->schedule();
        } catch (Throwable $e) {
            Log::warning('Reminder scheduling failed: '.$e->getMessage());
        }

        return back();
    }
}
