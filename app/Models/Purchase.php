<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use LogicException;

class Purchase extends Model
{
    use HasFactory;

    protected $fillable = [
        'invoice_number',
        'supplier_invoice_ref',
        'supplier_id',
        'warehouse_id',
        'user_id',
        'issue_date',
        'status',
        'subtotal',
        'tax_amount',
        'discount_amount',
        'total_amount',
        'paid_amount',
        'approved_at',
        'approved_by_user_id',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'issue_date' => 'date',
            'approved_at' => 'datetime',
            'subtotal' => 'decimal:2',
            'tax_amount' => 'decimal:2',
            'discount_amount' => 'decimal:2',
            'total_amount' => 'decimal:2',
            'paid_amount' => 'decimal:2',
        ];
    }

    protected static function booted(): void
    {
        static::deleting(function (Purchase $purchase) {
            if ($purchase->status !== 'draft') {
                throw new LogicException('لا يمكن حذف فاتورة مشتريات معتمدة للحفاظ على سلامة القيود التاريخية.');
            }
        });
    }

    public static function generateInvoiceNumber(): string
    {
        $year = date('Y');
        $prefix = "PO-{$year}-";
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

    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class);
    }

    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function approvedByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by_user_id');
    }

    public function items(): HasMany
    {
        return $this->hasMany(PurchaseItem::class);
    }

    public function movements(): MorphMany
    {
        return $this->morphMany(StockMovement::class, 'reference');
    }

    public function isDraft(): bool
    {
        return $this->status === 'draft';
    }

    public function isApproved(): bool
    {
        return $this->status === 'approved';
    }

    public function isCancelled(): bool
    {
        return $this->status === 'cancelled';
    }

    public function canBeEdited(): bool
    {
        return $this->isDraft();
    }

    public function canBeApproved(): bool
    {
        return $this->isDraft() && $this->items()->exists();
    }

    public function canBeDeleted(): bool
    {
        return $this->isDraft();
    }

    public function scopeSearch(Builder $query, ?string $term): Builder
    {
        if (blank($term)) {
            return $query;
        }

        return $query->where(function ($q) use ($term) {
            $q->where('invoice_number', 'like', "%{$term}%")
                ->orWhere('supplier_invoice_ref', 'like', "%{$term}%")
                ->orWhereHas('supplier', function ($sq) use ($term) {
                    $sq->where('name', 'like', "%{$term}%")
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
}
