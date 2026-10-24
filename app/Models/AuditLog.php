<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class AuditLog extends Model
{
    use HasFactory;

    public $timestamps = false;

    protected $fillable = [
        'user_id',
        'event',
        'auditable_type',
        'auditable_id',
        'description',
        'old_values',
        'new_values',
        'ip_address',
        'user_agent',
        'created_at',
    ];

    protected function casts(): array
    {
        return [
            'old_values' => 'array',
            'new_values' => 'array',
            'created_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function auditable(): MorphTo
    {
        return $this->morphTo();
    }

    public function eventLabel(): string
    {
        return match ($this->event) {
            'sale.created' => 'إنشاء مسودة بيع',
            'sale.completed' => 'اعتماد وصرف مبيعات',
            'sale.voided' => 'إلغاء فاتورة مبيعات',
            'purchase.created' => 'إنشاء مسودة شراء',
            'purchase.approved' => 'اعتماد وتوريد مشتريات',
            'transfer.created' => 'إنشاء أمر تحويل',
            'transfer.dispatched' => 'شحن تحويل مخزني',
            'transfer.received' => 'استلام تحويل مخزني',
            'auth.login' => 'تسجيل دخول للنظام',
            'auth.logout' => 'تسجيل خروج',
            'user.created' => 'إنشاء حساب مستخدم',
            'user.updated' => 'تعديل بيانات مستخدم',
            'user.deleted' => 'حذف حساب مستخدم',
            'product.created' => 'إضافة صنف جديد',
            'product.updated' => 'تعديل بيانات صنف',
            default => $this->event,
        };
    }

    public function eventBadgeClass(): string
    {
        return match ($this->event) {
            'sale.completed', 'purchase.approved', 'transfer.received' => 'badge bg-success-subtle text-success border border-success-subtle',
            'sale.voided' => 'badge bg-danger-subtle text-danger border border-danger-subtle',
            'transfer.dispatched' => 'badge bg-info-subtle text-info-emphasis border border-info-subtle',
            'auth.login', 'auth.logout' => 'badge bg-secondary-subtle text-secondary border',
            'sale.created', 'purchase.created', 'transfer.created' => 'badge bg-warning-subtle text-warning-emphasis border border-warning-subtle',
            default => 'badge bg-light text-dark border',
        };
    }

    public function scopeSearch(Builder $query, ?string $term): Builder
    {
        if (blank($term)) {
            return $query;
        }

        return $query->where(function ($q) use ($term) {
            $q->where('description', 'like', "%{$term}%")
                ->orWhere('ip_address', 'like', "%{$term}%")
                ->orWhere('event', 'like', "%{$term}%")
                ->orWhereHas('user', function ($uq) use ($term) {
                    $uq->where('name', 'like', "%{$term}%")
                        ->orWhere('username', 'like', "%{$term}%");
                });
        });
    }

    public function scopeEvent(Builder $query, ?string $event): Builder
    {
        return $event ? $query->where('event', $event) : $query;
    }

    public function scopeDateRange(Builder $query, ?string $from, ?string $to): Builder
    {
        if ($from) {
            $query->where('created_at', '>=', $from.' 00:00:00');
        }
        if ($to) {
            $query->where('created_at', '<=', $to.' 23:59:59');
        }

        return $query;
    }
}
