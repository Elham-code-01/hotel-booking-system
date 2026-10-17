@props(['title', 'sub' => null])
<div class="page-head">
    <div>
        <div class="eyebrow">{{ config('app.name') }}</div>
        <h1>{{ $title }}</h1>
        @if ($sub)
            <p class="subhead">{{ $sub }}</p>
        @endif
    </div>
    <div class="head-actions">{{ $slot }}</div>
</div>
