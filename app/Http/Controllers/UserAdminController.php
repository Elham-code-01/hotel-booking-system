<?php

namespace App\Http\Controllers;

use App\Models\Hotel;
use App\Models\User;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;

class UserAdminController extends Controller
{
    public const ROLES = ['admin', 'staff', 'representative', 'viewer'];

    public function index(): View
    {
        $users = User::orderByRaw("CASE status WHEN 'pending' THEN 0 WHEN 'active' THEN 1 ELSE 2 END")
            ->orderBy('name')
            ->get();

        return view('users.index', [
            'users'  => $users,
            'roles'  => self::ROLES,
            'hotels' => Hotel::orderBy('name')->get(['id', 'name']),
        ]);
    }

    public function update(Request $request, User $user): RedirectResponse
    {
        $data = $request->validate([
            'role'     => ['required', Rule::in(self::ROLES)],
            'status'   => ['required', Rule::in(['active', 'blocked'])],
            'hotel_id' => ['nullable', 'integer', 'exists:hotels,id'],
        ]);

        // Only a representative has a hotel. Everyone else never keeps one.
        if ($data['role'] !== 'representative') {
            $data['hotel_id'] = null;
        } elseif (empty($data['hotel_id']) && $data['status'] === 'active') {
            return back()->with('error', 'Choose the hotel this representative is responsible for.');
        }

        $keepsAdminAccess = $data['role'] === 'admin' && $data['status'] === 'active';

        if ($user->is($request->user()) && ! $keepsAdminAccess) {
            return back()->with('error', 'You cannot remove your own administrator access.');
        }

        $wasActiveAdmin = $user->role === 'admin' && $user->status === 'active';
        $otherAdmins = User::where('role', 'admin')->where('status', 'active')->whereKeyNot($user->id)->count();

        if ($wasActiveAdmin && ! $keepsAdminAccess && $otherAdmins === 0) {
            return back()->with('error', 'At least one active administrator is required.');
        }

        // forceFill: role, status and hotel are never mass-assignable from a form.
        $user->forceFill($data)->save();

        Log::info('user.updated', [
            'by'     => $request->user()->email,
            'target' => $user->email,
            'role'   => $data['role'],
            'status' => $data['status'],
            'hotel'  => $data['hotel_id'],
        ]);

        return back()->with('success', $user->name.' was updated.');
    }
}
