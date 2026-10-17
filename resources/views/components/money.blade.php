@props(['value'])
@php($amount = round((float) $value, 2))
<span>{{ $amount < 0 ? '($'.number_format(abs($amount), 2).')' : '$'.number_format($amount, 2) }}</span>
