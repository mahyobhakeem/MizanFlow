<?php

namespace App\Services\Audit;

use App\Models\AuditLog;
use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Database\Eloquent\Model;

class AuditService
{
    /**
     * Sensitive keys that must NEVER be stored in audit logs.
     */
    protected array $hiddenKeys = [
        'password',
        'password_confirmation',
        'remember_token',
        'two_factor_secret',
        'two_factor_recovery_codes',
        'api_token',
        'secret',
        'token',
    ];

    /**
     * Record an audit log entry for significant system operations.
     */
    public function record(
        string $event,
        ?Model $auditable = null,
        ?string $description = null,
        ?array $oldValues = null,
        ?array $newValues = null,
        ?int $userId = null
    ): AuditLog {
        return AuditLog::create([
            'user_id' => $userId ?? auth()->id(),
            'event' => $event,
            'auditable_type' => $auditable ? get_class($auditable) : null,
            'auditable_id' => $auditable?->getKey(),
            'description' => $description !== null ? mb_substr($description, 0, 500) : null,
            'old_values' => $this->sanitize($oldValues),
            'new_values' => $this->sanitize($newValues),
            'ip_address' => request()?->ip(),
            'user_agent' => request()?->userAgent() ? substr(request()->userAgent(), 0, 500) : null,
            'created_at' => now(),
        ]);
    }

    /**
     * Sanitize values to prevent logging secrets, passwords or credentials.
     */
    public function sanitize(mixed $values): ?array
    {
        if (empty($values)) {
            return null;
        }

        if ($values instanceof Arrayable) {
            $values = $values->toArray();
        } elseif (is_object($values)) {
            $values = (array) $values;
        }

        if (! is_array($values)) {
            return null;
        }

        $clean = [];
        foreach ($values as $key => $val) {
            $lowerKey = strtolower((string) $key);
            $isSensitive = in_array($lowerKey, $this->hiddenKeys, true)
                || str_contains($lowerKey, 'password')
                || str_contains($lowerKey, 'secret')
                || str_contains($lowerKey, 'token')
                || str_contains($lowerKey, 'api_key')
                || str_contains($lowerKey, 'cvv')
                || str_contains($lowerKey, 'card_num');

            if ($isSensitive) {
                continue;
            }

            if (is_object($val) || is_array($val)) {
                $clean[$key] = $this->sanitize($val);
            } else {
                $clean[$key] = $val;
            }
        }

        return $clean;
    }
}
