@props(['title', 'icon' => '∅'])
<div class="empty-state">
    <div class="empty-icon">{{ $icon }}</div>
    <h3>{{ $title }}</h3>
    <p>{{ $slot }}</p>
</div>
