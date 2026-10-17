<div class="nav-label">Workspace</div>
<nav class="nav-list">
    @foreach ($items as $item)
        @php
            $params = $item['params'] ?? [];
            $active = request()->routeIs($item['route'])
                && (! isset($params['hotel']) || (int) request()->route('hotel')?->id === (int) $params['hotel']);
        @endphp
        <a href="{{ route($item['route'], $params) }}" class="nav-link {{ $active ? 'active' : '' }}" @if ($active) aria-current="page" @endif>
            <span class="nav-icon" aria-hidden="true">{{ $item['icon'] }}</span>{{ $item['label'] }}
        </a>
    @endforeach
</nav>
