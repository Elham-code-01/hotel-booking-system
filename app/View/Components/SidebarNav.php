<?php

namespace App\View\Components;

use App\Models\Hotel;
use Illuminate\Contracts\View\View;
use Illuminate\View\Component;

class SidebarNav extends Component
{
    public function render(): View
    {
        $role = auth()->user()?->role;

        $items = collect(config('navigation.items'))
            ->reject(fn ($item) => $item['hidden'] ?? false)
            ->reject(fn ($item) => in_array($role, $item['menu_hidden_for'] ?? [], true))
            ->filter(fn ($item) => in_array($role, $item['roles'], true))
            ->flatMap(fn ($item) => ($item['per_hotel'] ?? false) ? $this->perHotel($item) : [$item])
            ->values()
            ->all();

        return view('components.sidebar-nav', ['items' => $items]);
    }

    /** One link per active hotel, labelled with the hotel's current name. */
    private function perHotel(array $item): array
    {
        return Hotel::where('is_active', true)
            ->orderBy('id')
            ->get()
            ->map(fn (Hotel $hotel) => [
                'route'  => $item['route'],
                'params' => ['hotel' => $hotel->id],
                'label'  => $hotel->name.' bookings',
                'icon'   => $item['icon'],
            ])
            ->all();
    }
}
