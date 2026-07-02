<?php

namespace App\Application\Transactions;

use App\Domain\Enums\TransactionType;
use App\Domain\Models\Account;
use App\Domain\Models\Transaction;
use App\Domain\ValueObjects\Money;
use Carbon\CarbonInterface;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use InvalidArgumentException;

/**
 * Moves money from one account to another as a pair of linked transactions
 * sharing a transfer_group_id. Same-currency only for now; cross-currency
 * conversion arrives with the multi-currency phase.
 */
class TransferBetweenAccounts
{
    /**
     * @return array{out: Transaction, in: Transaction}
     */
    public function handle(
        Account $from,
        Account $to,
        Money $amount,
        ?string $description = null,
        ?CarbonInterface $occurredOn = null,
    ): array {
        if ($from->is($to)) {
            throw new InvalidArgumentException('Cannot transfer to the same account.');
        }

        if ($from->currency !== $to->currency || $amount->currency !== $from->currency) {
            throw new InvalidArgumentException('Cross-currency transfers are not supported yet.');
        }

        if ($amount->absolute()->minorUnits > $from->current_balance->minorUnits) {
            throw new InvalidArgumentException('Insufficient funds in the source account.');
        }

        $groupId = (string) Str::uuid();
        $date = $occurredOn ?? now();
        $magnitude = $amount->absolute();

        return DB::transaction(function () use ($from, $to, $magnitude, $description, $date, $groupId) {
            $out = $from->transactions()->create([
                'type' => TransactionType::Transfer,
                'amount' => $magnitude,
                'currency' => $from->currency,
                'is_inflow' => false,
                'description' => $description ?? "Transferencia a {$to->name}",
                'occurred_on' => $date,
                'transfer_group_id' => $groupId,
            ]);

            $in = $to->transactions()->create([
                'type' => TransactionType::Transfer,
                'amount' => $magnitude,
                'currency' => $to->currency,
                'is_inflow' => true,
                'description' => $description ?? "Transferencia desde {$from->name}",
                'occurred_on' => $date,
                'transfer_group_id' => $groupId,
            ]);

            return ['out' => $out, 'in' => $in];
        });
    }
}
