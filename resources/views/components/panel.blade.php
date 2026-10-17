@props(['title'])
<section class="panel">
    <div class="panel-head">
        <h2>{{ $title }}</h2>
        @isset($extra)
            {{ $extra }}
        @endisset
    </div>
    {{ $slot }}
</section>
