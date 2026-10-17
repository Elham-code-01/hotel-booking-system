@php($messages = array_filter(['success' => session('success'), 'error' => session('error')]))
@if ($messages)
    <div class="flash-region" role="status" aria-live="polite">
        @foreach ($messages as $type => $message)
            <div class="flash {{ $type === 'error' ? 'error' : '' }}">{{ $message }}</div>
        @endforeach
    </div>
@endif
