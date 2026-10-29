<?php

namespace App\Models\Finance;

use App\Models\User;
use Database\Factories\Finance\BillPaymentFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Pagamento de uma fatura de cartão (ver migration create_bill_payments_table).
 * transaction_id nulo = fatura apenas marcada como paga, sem lançamento.
 */
class BillPayment extends Model
{
    /** @use HasFactory<BillPaymentFactory> */
    use HasFactory;

    protected $fillable = [
        'user_id',
        'account_id',
        'bill_identifier',
        'transaction_id',
        'amount',
        'paid_at',
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'paid_at' => 'date',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /** Cartão da fatura. */
    public function account(): BelongsTo
    {
        return $this->belongsTo(Account::class)->withTrashed();
    }

    /** Transferência que pagou a fatura (null quando só foi marcada como paga). */
    public function transaction(): BelongsTo
    {
        return $this->belongsTo(Transaction::class);
    }
}
