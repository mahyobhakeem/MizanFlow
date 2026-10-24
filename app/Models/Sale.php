<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use LogicException;

class Sale extends Model
{
    use HasFactory;

    protected $fillable = [
        'invoice_number',
        'customer_id',
        'warehouse_id',
        'user_id',
        'invoice_date',
        'payment_type',
        'status',
        'subtotal',
        'tax_amount',
        'discount_amount',
        'total_amount',
        'paid_amount',
        'completed_at',
        'voided_at',
        'voided_by_user_id',
        'void_reason',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'invoice_date' => 'datetime',
            'completed_at' => 'datetime',
            'voided_at' => 'datetime',
            'subtotal' => 'decimal:2',
            'tax_amount' => 'decimal:2',
            'discount_amount' => 'decimal:2',
            'total_amount' => 'decimal:2',
            'paid_amount' => 'decimal:2',
        ];
    }

    protected static function booted(): void
    {
        static::deleting(function (Sale $sale) {
            if (! $sale->isDraft()) {
                throw new LogicException('لا يمكن حذف فاتورة مبيعات معتمدة للحفاظ على سلامة القيود المحاسبية والمخزنية.');
            }
        });
    }

    public static function generateInvoiceNumber(): string
    {
        $year = date('Y');
        $prefix = "INV-{$year}-";
        $last = static::where('invoice_number', 'like', "{$prefix}%")
            ->orderByDesc('id')
            ->value('invoice_number');

        if ($last && preg_match('/-(\d+)$/', $last, $matches)) {
            $nextSeq = str_pad((int) $matches[1] + 1, 6, '0', STR_PAD_LEFT);
        } else {
            $nextSeq = '000001';
        }

        return $prefix.$nextSeq;
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function voidedByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'voided_by_user_id');
    }

    public function items(): HasMany
    {
        return $this->hasMany(SaleItem::class);
    }

    public function movements(): MorphMany
    {
        return $this->morphMany(StockMovement::class, 'reference');
    }

    public function isDraft(): bool
    {
        return $this->status === 'draft';
    }

    public function isCompleted(): bool
    {
        return $this->status === 'completed';
    }

    public function isVoided(): bool
    {
        return $this->status === 'voided';
    }

    public function isPartiallyReturned(): bool
    {
        return $this->status === 'partially_returned';
    }

    public function canBeEdited(): bool
    {
        return $this->isDraft();
    }

    public function canBeCompleted(): bool
    {
        return $this->isDraft() && $this->items()->exists();
    }

    public function canBeVoided(): bool
    {
        return $this->isCompleted();
    }

    public function canBeDeleted(): bool
    {
        return $this->isDraft();
    }

    public function remainingAmount(): float
    {
        return max(0.0, round((float) $this->total_amount - (float) $this->paid_amount, 2));
    }

    public function totalGrossProfit(): float
    {
        return (float) $this->items->sum(function (SaleItem $item) {
            return $item->totalProfit();
        });
    }

    public function paymentTypeLabel(): string
    {
        return match ($this->payment_type) {
            'cash' => 'نقدي',
            'card' => 'شبكة / بطاقة',
            'credit' => 'آجل',
            'split' => 'دفع مجزأ (نقدي + آجل)',
            default => $this->payment_type,
        };
    }

    public function statusLabel(): string
    {
        return match ($this->status) {
            'draft' => 'مسودة',
            'completed' => 'معتمدة ومكتملة',
            'voided' => 'ملغاة (حركة عكسية)',
            'partially_returned' => 'مردود جزئي',
            default => $this->status,
        };
    }

    public function statusBadgeClass(): string
    {
        return match ($this->status) {
            'draft' => 'badge bg-warning text-dark',
            'completed' => 'badge bg-success',
            'voided' => 'badge bg-danger',
            'partially_returned' => 'badge bg-info text-dark',
            default => 'badge bg-secondary',
        };
    }

    public function scopeSearch(Builder $query, ?string $term): Builder
    {
        if (blank($term)) {
            return $query;
        }

        return $query->where(function ($q) use ($term) {
            $q->where('invoice_number', 'like', "%{$term}%")
                ->orWhereHas('customer', function ($cq) use ($term) {
                    $cq->where('name', 'like', "%{$term}%")
                        ->orWhere('phone', 'like', "%{$term}%")
                        ->orWhere('code', 'like', "%{$term}%");
                });
        });
    }

    public function scopeStatus(Builder $query, ?string $status): Builder
    {
        return $status ? $query->where('status', $status) : $query;
    }

    public function scopeWarehouse(Builder $query, ?int $warehouseId): Builder
    {
        return $warehouseId ? $query->where('warehouse_id', $warehouseId) : $query;
    }

    public function scopePaymentType(Builder $query, ?string $paymentType): Builder
    {
        return $paymentType ? $query->where('payment_type', $paymentType) : $query;
    }
}
