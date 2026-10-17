<x-app-layout>
    <x-slot name="header">Activity log</x-slot>

    <x-page-header title="Activity log" sub="Who did what, and when. Entries can never be edited or deleted here." />

    <x-panel title="Filter">
        <form method="GET" class="toolbar">
            <select name="action" aria-label="Action">
                <option value="">All actions</option>
                @foreach ($actions as $action)
                    <option value="{{ $action }}" @selected(($filters['action'] ?? null) === $action)>{{ $action }}</option>
                @endforeach
            </select>
            <select name="user_id" aria-label="User">
                <option value="">All users</option>
                @foreach ($users as $user)
                    <option value="{{ $user->id }}" @selected((int) ($filters['user_id'] ?? 0) === $user->id)>{{ $user->name }}</option>
                @endforeach
            </select>
            <button class="btn btn-sm btn-primary" type="submit">Filter</button>
        </form>
    </x-panel>

    <x-panel title="Entries">
        @if ($logs->isEmpty())
            <x-empty-state title="Nothing recorded yet" icon="☷">Actions appear here as people use the system.</x-empty-state>
        @else
            <div class="table-wrap">
                <table>
                    <thead><tr><th>When</th><th>User</th><th>Action</th><th>Details</th><th>IP</th></tr></thead>
                    <tbody>
                        @foreach ($logs as $log)
                            <tr>
                                <td>{{ $log->created_at->format('Y-m-d H:i:s') }}</td>
                                <td>{{ $log->user?->name ?? '—' }}</td>
                                <td><strong>{{ $log->action }}</strong></td>
                                <td style="white-space:normal;max-width:420px">
                                    {{ collect($log->details ?? [])->map(fn ($value, $key) => $key.': '.(is_scalar($value) || $value === null ? $value : json_encode($value)))->join(' · ') }}
                                </td>
                                <td>{{ $log->ip }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            <div style="margin-top:14px">{{ $logs->links() }}</div>
        @endif
    </x-panel>
</x-app-layout>
