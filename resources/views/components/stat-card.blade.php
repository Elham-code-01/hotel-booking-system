@props(['label', 'value', 'note' => '', 'mark' => '•'])
<article class="stat-card">
    <span class="stat-mark">{{ $mark }}</span>
    <div class="stat-label">{{ $label }}</div>
    <div class="stat-value">{{ $value }}</div>
    <div class="stat-note">{{ $note }}</div>
</article>
