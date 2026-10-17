<?php

namespace App\Support;

use App\Models\AuditLog;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * The single way to write to the activity log.
 * Never put passwords or other secrets in $details.
 */
class Audit
{
    /** @param array<string, mixed> $details */
    public static function record(string $action, array $details = []): void
    {
        try {
            AuditLog::create([
                'user_id' => auth()->id(),
                'action'  => $action,
                'details' => $details ?: null,
                'ip'      => request()->ip(),
            ]);
        } catch (Throwable $e) {
            // A problem with the log must never stop the real work. It is reported to the file log instead.
            Log::warning('audit.failed', ['action' => $action, 'error' => $e->getMessage()]);
        }
    }
}
