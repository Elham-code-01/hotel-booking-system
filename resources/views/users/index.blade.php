<x-app-layout>
    <x-slot name="header">Users</x-slot>

    <x-page-header title="Users" sub="Approve new accounts, choose their role, or block access." />

    <x-panel title="Accounts">
        <div class="table-wrap">
            <table>
                <thead>
                    <tr>
                        <th>Name</th>
                        <th>Email</th>
                        <th>Status</th>
                        <th>Role and action</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($users as $user)
                        <tr>
                            <td>{{ $user->name }}</td>
                            <td>{{ $user->email }}</td>
                            <td><x-status-badge :status="$user->status === 'active' ? 'confirmed' : ($user->status === 'pending' ? 'tentative' : 'cancelled')" /> <span class="muted">{{ $user->status }}</span></td>
                            <td>
                                <form method="POST" action="{{ route('users.update', $user) }}" class="toolbar" style="margin:0">
                                    @csrf
                                    @method('PATCH')
                                    <select name="role" aria-label="Role for {{ $user->name }}">
                                        @foreach ($roles as $role)
                                            <option value="{{ $role }}" @selected($user->role === $role)>{{ ucfirst($role) }}</option>
                                        @endforeach
                                    </select>
                                    <select name="hotel_id" aria-label="Hotel for {{ $user->name }} (representatives only)">
                                        <option value="">No hotel</option>
                                        @foreach ($hotels as $hotel)
                                            <option value="{{ $hotel->id }}" @selected($user->hotel_id === $hotel->id)>{{ $hotel->name }}</option>
                                        @endforeach
                                    </select>
                                    <button type="submit" name="status" value="active" class="btn btn-sm btn-primary">{{ $user->status === 'pending' ? 'Approve' : 'Save' }}</button>
                                    @if ($user->status !== 'blocked')
                                        <button type="submit" name="status" value="blocked" class="btn btn-sm btn-danger">Block</button>
                                    @endif
                                </form>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        <p class="muted" style="margin-top:12px">The hotel is used only for a <strong>Representative</strong>: they see that hotel and nothing else. Roles: <strong>Admin</strong> everything · <strong>Staff</strong> bookings and accounts · <strong>Representative</strong> room status only · <strong>Viewer</strong> read only.</p>
    </x-panel>
</x-app-layout>
