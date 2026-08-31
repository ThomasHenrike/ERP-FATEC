@props(['data', 'color' => '#2a78d6', 'prefix' => '', 'suffix' => '', 'unitLabel' => ''])

@php
    $max = collect($data)->max('value') ?: 1;
    $barCount = max(count($data), 1);
    $chartWidth = 560;
    $chartHeight = 210;
    $plotHeight = 150;
    $bandWidth = $chartWidth / $barCount;
    $barWidth = min(28, $bandWidth * 0.55);

    $formatValue = fn ($value) => $prefix . number_format($value, 0, ',', '.') . $suffix;
@endphp

<div class="relative" x-data="{ tip: null, showTable: false }">
    <svg viewBox="0 0 {{ $chartWidth }} {{ $chartHeight }}" class="w-full h-auto" style="max-height: 220px" x-show="!showTable">
        <line x1="0" y1="{{ $plotHeight }}" x2="{{ $chartWidth }}" y2="{{ $plotHeight }}" stroke="#c3c2b7" stroke-width="1" />

        @foreach ($data as $i => $point)
            @php
                $barHeight = $max > 0 ? (int) round(($point['value'] / $max) * ($plotHeight - 10)) : 0;
                $barHeight = max($barHeight, $point['value'] > 0 ? 3 : 0);
                $x = round($i * $bandWidth + ($bandWidth - $barWidth) / 2, 1);
                $y = $plotHeight - $barHeight;
                $isLast = $i === $barCount - 1;
                $formatted = $formatValue($point['value']);
            @endphp
            <g>
                <rect x="{{ $x }}" y="{{ $y }}" width="{{ $barWidth }}" height="{{ $barHeight }}"
                      rx="4" fill="{{ $color }}"
                      @mouseenter="tip = { xPct: {{ round((($x + $barWidth / 2) / $chartWidth) * 100, 2) }}, yPct: {{ round(($y / $chartHeight) * 100, 2) }}, label: '{{ $point['label'] }}', value: '{{ $formatted }}' }"
                      @mouseleave="tip = null"
                      @focus="tip = { xPct: {{ round((($x + $barWidth / 2) / $chartWidth) * 100, 2) }}, yPct: {{ round(($y / $chartHeight) * 100, 2) }}, label: '{{ $point['label'] }}', value: '{{ $formatted }}' }"
                      @blur="tip = null"
                      tabindex="0"
                      class="cursor-pointer"
                      :opacity="tip && tip.label !== '{{ $point['label'] }}' ? 0.6 : 1"></rect>
                @if ($isLast)
                    <text x="{{ $x + $barWidth / 2 }}" y="{{ $y - 6 }}" text-anchor="middle" font-size="11" font-weight="700" fill="#0b0b0b">{{ $formatted }}</text>
                @endif
                <text x="{{ $x + $barWidth / 2 }}" y="{{ $plotHeight + 18 }}" text-anchor="middle" font-size="10" fill="#898781">{{ $point['label'] }}</text>
            </g>
        @endforeach
    </svg>

    <div x-show="tip" x-cloak
         class="absolute pointer-events-none bg-gray-900 text-white text-xs rounded-lg px-2.5 py-1.5 shadow-lg z-10"
         :style="tip ? `left:${tip.xPct}%; top:${tip.yPct}%; transform: translate(-50%, -110%)` : ''">
        <span x-text="tip?.label"></span>: <strong x-text="tip?.value"></strong>
    </div>

    <template x-if="showTable">
        <table class="min-w-full text-xs text-left">
            <thead class="text-gray-500 uppercase">
                <tr><th class="py-1 pr-4">Mes</th><th class="py-1">Valor</th></tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                @foreach ($data as $point)
                    <tr><td class="py-1 pr-4 font-medium text-gray-700">{{ $point['label'] }}</td><td class="py-1 font-mono">{{ $formatValue($point['value']) }}</td></tr>
                @endforeach
            </tbody>
        </table>
    </template>

    <button type="button" @click="showTable = !showTable" class="mt-2 text-[11px] font-semibold text-gray-400 hover:text-gray-600 underline">
        <span x-text="showTable ? 'Ver como grafico' : 'Ver como tabela'"></span>
    </button>
</div>
