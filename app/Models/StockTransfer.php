<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use LogicException;

class StockTransfer extends Model
{
    use HasFactory;

    protected $fillable = [
        'transfer_number',
        'from_warehouse_id',
        'to_warehouse_id',
        'created_by_user_id',
        'received_by_user_id',
        'status',
        'dispatched_at',
        'received_at',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'dispatched_at' => 'datetime',
            'received_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::deleting(function (StockTransfer $transfer) {
            if ($transfer->status !== 'draft') {
                throw new LogicException('لا يمكن حذف أمر تحويل مخزني بعد تنفيذه أو شحنه.');
            }
        });
    }

    public static function generateTransferNumber(): string
    {
        $year = date('Y');
        $prefix = "TR-{$year}-";
        $last = static::where('transfer_number', 'like', "{$prefix}%")
            ->orderByDesc('id')
            ->value('transfer_number');

        if ($last && preg_match('/-(\d+)$/', $last, $matches)) {
            $nextSeq = str_pad((int) $matches[1] + 1, 6, '0', STR_PAD_LEFT);
        } else {
            $nextSeq = '000001';
        }

        return $prefix.$nextSeq;
    }

    public function fromWarehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class, 'from_warehouse_id');
    }

    public function toWarehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class, 'to_warehouse_id');
    }

    public function createdByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by_user_id');
    }

    public function receivedByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'received_by_user_id');
    }

    public function items(): HasMany
    {
        return $this->hasMany(StockTransferItem::class);
    }

    public function movements(): MorphMany
    {
        return $this->morphMany(StockMovement::class, 'reference');
    }

    public function isDraft(): bool
    {
        return $this->status === 'draft';
    }

    public function isInTransit(): bool
    {
        return $this->status === 'in_transit';
    }

    public function isCompleted(): bool
    {
        return $this->status === 'completed';
    }

    public function isCancelled(): bool
    {
        return $this->status === 'cancelled';
    }

    public function canBeDispatched(): bool
    {
        return $this->isDraft() && $this->items()->exists();
    }

    public function canBeReceived(): bool
    {
        return $this->isInTransit();
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
            $q->where('transfer_number', 'like', "%{$term}%")
                ->orWhereHas('fromWarehouse', function ($fq) use ($term) {
                    $fq->where('name', 'like', "%{$term}%")
                        ->orWhere('code', 'like', "%{$term}%");
                })
                ->orWhereHas('toWarehouse', function ($tq) use ($term) {
                    $tq->where('name', 'like', "%{$term}%")
                        ->orWhere('code', 'like', "%{$term}%");
                });
        });
    }

    public function scopeStatus(Builder $query, ?string $status): Builder
    {
        return $status ? $query->where('status', $status) : $query;
    }
}
