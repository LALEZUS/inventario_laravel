<?php

namespace App\Services;

use App\Models\AuditLog;
use Illuminate\Contracts\Auth\Authenticatable;

class AuditLogger
{
    private const UTF8_SENSITIVE_FIELDS = ['contraseña'];

    private const SENSITIVE_FIELDS = [
        'password', 'updated_password', 'admin_password', 'app_lock_password',
        'app_lock_answer', 'app_lock_pattern', 'remember_token', 'contraseña',
        'key_value', 'license_key', 'product_key', 'recovery_codes',
    ];

    private const PRESERVED_SECRET_ENTITIES = [
        'licenses',
        'account_management',
        'correos_outlook',
        'microsoft_emails',
        'office_emails',
    ];

    public function record(
        string $action,
        string $entity,
        string|int|null $entityId = null,
        ?array $before = null,
        ?array $after = null,
        ?Authenticatable $user = null,
    ): AuditLog {
        $user ??= auth()->user();

        return AuditLog::create([
            'user_id' => $user?->getAuthIdentifier(),
            'user_name' => $user?->display_name ?? $user?->username,
            'role' => $user?->role,
            'action' => $action,
            'entity' => $entity,
            'entity_id' => $entityId,
            'before_data' => $this->sanitize($before, $entity),
            'after_data' => $this->sanitize($after, $entity),
            'ip_address' => request()->ip(),
        ]);
    }

    private function sanitize(?array $data, ?string $entity = null): ?array
    {
        if ($data === null) {
            return null;
        }

        $preserveCredentials = in_array($entity, self::PRESERVED_SECRET_ENTITIES, true);

        foreach (array_merge(self::SENSITIVE_FIELDS, self::UTF8_SENSITIVE_FIELDS) as $field) {
            if ($preserveCredentials && in_array($field, ['password', 'contraseña', 'key_value', 'license_key', 'product_key'], true)) {
                continue;
            }

            if (array_key_exists($field, $data)) {
                $data[$field] = filled($data[$field]) ? '[PROTECTED]' : null;
            }
        }

        return $data;
    }
}
