<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\User;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;

class AuditController extends Controller
{
    public function index(Request $request): View
    {
        $filters = $request->validate([
            'action'  => ['nullable', 'string', 'max:60'],
            'user_id' => ['nullable', 'integer', 'exists:users,id'],
        ]);

        $logs = AuditLog::query()
            ->with('user:id,name,email')
            ->when($filters['action'] ?? null, fn ($q, $action) => $q->where('action', $action))
            ->when($filters['user_id'] ?? null, fn ($q, $user) => $q->where('user_id', $user))
            ->orderByDesc('id')
            ->paginate(50)
            ->withQueryString();

        return view('audit.index', [
            'logs'    => $logs,
            'filters' => $filters,
            'actions' => AuditLog::query()->select('action')->distinct()->orderBy('action')->pluck('action'),
            'users'   => User::orderBy('name')->get(['id', 'name']),
        ]);
    }
}
