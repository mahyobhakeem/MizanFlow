<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Customer extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'code',
        'name',
        'company_name',
        'phone',
        'email',
        'tax_number',
        'address',
        'credit_limit',
        'balance',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'credit_limit' => 'decimal:2',
            'balance' => 'decimal:2',
            'is_active' => 'boolean',
        ];
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    public function scopeSearch(Builder $query, ?string $term): Builder
    {
        if (blank($term)) {
            return $query;
        }

        return $query->where(function ($q) use ($term) {
            $q->where('name', 'like', "%{$term}%")
                ->orWhere('code', 'like', "%{$term}%")
                ->orWhere('phone', 'like', "%{$term}%")
                ->orWhere('company_name', 'like', "%{$term}%");
        });
    }

    public function sales(): HasMany
    {
        return $this->hasMany(Sale::class);
    }

    public function canBeDeleted(): bool
    {
        // Prevent deleting if customer has non-zero balance or associated sales invoices
        return (float) $this->balance == 0.0 && $this->sales()->count() === 0;
    }
}
