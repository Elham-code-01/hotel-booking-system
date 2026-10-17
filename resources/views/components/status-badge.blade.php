@props(['status', 'label' => null])
<span class="badge {{ str_replace(' ', '-', strtolower($status)) }}">{{ $label ?? ucwords(str_replace('-', ' ', $status)) }}</span>
