@props(['status'])

@php
    $labels = [
        'draft' => 'Rascunho',
        'signed' => 'Assinada',
        'transmitted' => 'Transmitida',
        'authorized' => 'Autorizada',
        'rejected' => 'Rejeitada',
        'denied' => 'Denegada',
        'error' => 'Erro',
        'cancelled' => 'Cancelada',
    ];

    $colors = [
        'draft' => 'bg-gray-100 text-gray-600',
        'signed' => 'bg-indigo-50 text-indigo-700',
        'transmitted' => 'bg-indigo-50 text-indigo-700',
        'authorized' => 'bg-emerald-100 text-emerald-800',
        'rejected' => 'bg-rose-100 text-rose-800',
        'denied' => 'bg-rose-100 text-rose-800',
        'error' => 'bg-amber-100 text-amber-800',
        'cancelled' => 'bg-gray-200 text-gray-700',
    ];
@endphp

<span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-bold uppercase {{ $colors[$status] ?? 'bg-gray-100 text-gray-600' }}">
    {{ $labels[$status] ?? $status }}
</span>
