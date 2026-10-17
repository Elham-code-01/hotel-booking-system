<?php

namespace App\View\Components;

use App\Services\AlertService;
use Illuminate\Contracts\View\View;
use Illuminate\View\Component;

class Notifications extends Component
{
    public function __construct(private AlertService $alerts)
    {
    }

    public function render(): View
    {
        return view('components.notifications', [
            'alerts' => $this->alerts->forUser(auth()->user()),
        ]);
    }
}
