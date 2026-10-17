<div class="notify">
    <button type="button" class="icon-btn" data-toggle="notify-panel" aria-label="Notifications" aria-expanded="false" aria-controls="notify-panel">
        <span aria-hidden="true">🔔</span>
        @if (count($alerts) > 0)
            <span class="notify-count">{{ count($alerts) }}</span>
        @endif
    </button>
    <div class="notify-panel" id="notify-panel" hidden>
        @forelse ($alerts as $alert)
            <a href="{{ $alert['url'] }}" class="notify-item {{ $alert['level'] }}">
                <strong>{{ $alert['title'] }}</strong>
                <span>{{ $alert['text'] }}</span>
            </a>
        @empty
            <div class="notify-empty">You are all caught up.</div>
        @endforelse
    </div>
</div>
