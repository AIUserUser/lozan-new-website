@extends('layouts.admin')

@section('wide', true)

@push('head')
    <link rel="stylesheet" href="/css/admin-analytics.css?v={{ filemtime(public_path('css/admin-analytics.css')) }}">
@endpush

@php
    $t = fn (string $key, array $replace = []) => lozan_t('admin.analytics.'.$key, $replace);
    $totals = $report['totals'];
    $num = fn ($n) => number_format((float) $n);
    $isRtl = app()->getLocale() === 'ar';
    $maxChoice = fn (array $rows) => max(1, ...array_map(fn ($r) => $r['carts'] + $r['ordered'], $rows ?: [['carts' => 0, 'ordered' => 0]]));
@endphp

@section('content')
<div class="lz-analytics flex flex-col gap-6 text-[#1a1a1a]">
    <header class="flex flex-wrap items-end justify-between gap-4">
        <div>
            <h1 class="m-0 text-3xl">{{ $t('title') }}</h1>
            <p class="mt-1 mb-0 text-sm text-[#52514e]">{{ $t('subtitle') }}</p>
        </div>
        <nav class="inline-flex rounded-md border border-[#e8e4dc] bg-white p-1 text-sm" aria-label="{{ $t('rangeA11y') }}">
            @foreach(\App\Services\Analytics\AnalyticsReport::RANGES as $range)
                <a href="{{ route('admin.analytics', ['range' => $range]) }}"
                   @if($report['days'] === $range) aria-current="page" @endif
                   class="rounded px-3 py-1.5 no-underline transition-colors {{ $report['days'] === $range ? 'bg-[#1a1a1a] text-white' : 'text-[#52514e] hover:bg-[#faf8f3]' }}">
                    {{ $t('range.d'.$range) }}
                </a>
            @endforeach
        </nav>
    </header>

    @unless($report['hasEvents'])
        <p class="m-0 rounded-md border border-[#c5a02d]/40 bg-[#c5a02d]/10 px-4 py-3 text-sm">{{ $t('noData') }}</p>
    @endunless

    {{-- KPI tiles --}}
    <section class="grid grid-cols-2 gap-3 md:grid-cols-3 xl:grid-cols-6">
        @foreach([
            ['label' => $t('kpi.visitors'), 'value' => $num($totals['visitors']), 'note' => $t('kpi.visitorsNote', ['new' => $num($totals['newVisitors']), 'returning' => $num($totals['returningVisitors'])]).' · '.$t('kpi.visits', ['count' => $num($totals['visits'])])],
            ['label' => $t('kpi.productViews'), 'value' => $num($totals['productViews']), 'note' => $t('kpi.pageViews').': '.$num($totals['pageViews'])],
            ['label' => $t('kpi.addToCarts'), 'value' => $num($totals['addToCarts']), 'note' => null],
            ['label' => $t('kpi.orders'), 'value' => $num($totals['orders']), 'note' => $t('kpi.averageOrder', ['value' => format_kwd($totals['averageOrder'])])],
            ['label' => $t('kpi.revenue'), 'value' => format_kwd($totals['revenue']), 'note' => null],
            ['label' => $t('kpi.conversion'), 'value' => number_format($totals['conversionRate'], 1).'%', 'note' => $t('kpi.conversionNote')],
        ] as $kpi)
            <div class="min-w-0 rounded-lg border border-[#e8e4dc] bg-white p-4">
                <p class="m-0 text-xs font-medium uppercase tracking-wide text-[#52514e]">{{ $kpi['label'] }}</p>
                <p class="mt-2 mb-0 text-2xl font-semibold leading-tight">{{ $kpi['value'] }}</p>
                @if($kpi['note'])
                    <p class="mt-1 mb-0 text-xs text-[#898781]">{{ $kpi['note'] }}</p>
                @endif
            </div>
        @endforeach
    </section>

    {{-- Trend + funnel --}}
    <section class="grid grid-cols-1 gap-6 lg:grid-cols-3">
        <div class="min-w-0 rounded-lg border border-[#e8e4dc] bg-white p-5 lg:col-span-2">
            <h2 class="m-0 text-lg">{{ $t('trend.title') }}</h2>
            <div class="relative mt-4 h-72">
                <canvas id="chart-trend" role="img" aria-label="{{ $t('trend.title') }}"></canvas>
            </div>
            <details class="mt-3 text-sm">
                <summary class="cursor-pointer text-[#52514e]">{{ $t('tableView') }}</summary>
                <div class="mt-2 max-h-64 overflow-auto">
                    <table class="w-full text-start tabular-nums">
                        <thead><tr class="text-[#52514e]"><th class="py-1 text-start">{{ $t('day') }}</th><th class="py-1 text-end">{{ $t('trend.visitors') }}</th><th class="py-1 text-end">{{ $t('trend.productViews') }}</th></tr></thead>
                        <tbody>
                            @foreach($report['daily']['labels'] as $i => $day)
                                <tr class="border-t border-[#e8e4dc]"><td class="py-1">{{ $day }}</td><td class="py-1 text-end">{{ $report['daily']['visitors'][$i] }}</td><td class="py-1 text-end">{{ $report['daily']['productViews'][$i] }}</td></tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </details>
        </div>

        <div class="min-w-0 rounded-lg border border-[#e8e4dc] bg-white p-5">
            <h2 class="m-0 text-lg">{{ $t('funnel.title') }}</h2>
            <p class="mt-1 mb-0 text-xs text-[#898781]">{{ $t('funnel.note') }}</p>
            <ol class="m-0 mt-4 flex list-none flex-col gap-4 p-0">
                @foreach($report['funnel'] as $step)
                    <li>
                        <div class="flex items-baseline justify-between gap-2 text-sm">
                            <span>{{ $t('funnel.steps.'.$step['key']) }}</span>
                            <span class="tabular-nums"><strong>{{ $num($step['count']) }}</strong> <span class="text-[#898781]">· {{ $step['percent'] }}%</span></span>
                        </div>
                        <div class="mt-1.5 h-2 rounded-full bg-[#f0efec]">
                            <div class="h-2 rounded-full bg-[#2a78d6]" style="width: {{ max($step['count'] > 0 ? 2 : 0, $step['percent']) }}%"></div>
                        </div>
                    </li>
                @endforeach
            </ol>
        </div>
    </section>

    {{-- Top products + hours --}}
    <section class="grid grid-cols-1 gap-6 lg:grid-cols-2">
        <div class="min-w-0 rounded-lg border border-[#e8e4dc] bg-white p-5">
            <h2 class="m-0 text-lg">{{ $t('top.title') }}</h2>
            @php $topProducts = array_slice(array_values(array_filter($report['products'], fn ($p) => $p['views'] > 0)), 0, 8); @endphp
            @if($topProducts)
                <div class="relative mt-4" style="height: {{ 3 + count($topProducts) * 2.25 }}rem">
                    <canvas id="chart-products" role="img" aria-label="{{ $t('top.title') }}"></canvas>
                </div>
            @else
                <p class="mt-4 mb-0 text-sm text-[#898781]">{{ $t('none') }}</p>
            @endif
        </div>

        <div class="min-w-0 rounded-lg border border-[#e8e4dc] bg-white p-5">
            <h2 class="m-0 text-lg">{{ $t('hours.title') }}</h2>
            <div class="relative mt-4 h-64">
                <canvas id="chart-hours" role="img" aria-label="{{ $t('hours.title') }}"></canvas>
            </div>
            <details class="mt-3 text-sm">
                <summary class="cursor-pointer text-[#52514e]">{{ $t('tableView') }}</summary>
                <div class="mt-2 grid grid-cols-3 gap-x-4 tabular-nums sm:grid-cols-4">
                    @foreach($report['hours'] as $h => $views)
                        <div class="flex justify-between border-t border-[#e8e4dc] py-1"><span>{{ sprintf('%02d:00', $h) }}</span><span>{{ $views }}</span></div>
                    @endforeach
                </div>
            </details>
        </div>
    </section>

    {{-- Product engagement table --}}
    <section class="min-w-0 rounded-lg border border-[#e8e4dc] bg-white p-5">
        <h2 class="m-0 text-lg">{{ $t('products.title') }}</h2>
        <p class="mt-1 mb-0 text-xs text-[#898781]">{{ $t('products.note') }}</p>
        @if(empty($report['products']))
            <p class="mt-4 mb-0 text-sm text-[#898781]">{{ $t('products.empty') }}</p>
        @else
            <div class="mt-4 overflow-x-auto">
                <table class="w-full min-w-[44rem] border-collapse text-sm tabular-nums">
                    <thead>
                        <tr class="border-b border-[#e8e4dc] text-xs text-[#52514e]">
                            <th class="py-2 pe-3 text-start font-medium">{{ $t('products.product') }}</th>
                            <th class="px-3 py-2 text-end font-medium">{{ $t('products.views') }}</th>
                            <th class="px-3 py-2 text-end font-medium">{{ $t('products.viewers') }}</th>
                            <th class="px-3 py-2 text-end font-medium">{{ $t('products.carts') }}</th>
                            <th class="px-3 py-2 text-end font-medium">{{ $t('products.cartRate') }}</th>
                            <th class="px-3 py-2 text-end font-medium">{{ $t('products.units') }}</th>
                            <th class="ps-3 py-2 text-end font-medium">{{ $t('products.revenue') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($report['products'] as $p)
                            <tr class="border-b border-[#e8e4dc] last:border-0">
                                <td class="py-2 pe-3">
                                    <a href="{{ $p['editUrl'] }}" class="flex items-center gap-3 text-inherit no-underline hover:underline">
                                        @if($p['image'])
                                            <img src="{{ $p['image'] }}" alt="" width="36" height="46" loading="lazy" class="h-[46px] w-9 shrink-0 rounded object-cover">
                                        @else
                                            <span class="h-[46px] w-9 shrink-0 rounded bg-[#faf8f3]"></span>
                                        @endif
                                        <span>{{ $p['name'] }}</span>
                                        @unless($p['published'])
                                            <span class="rounded bg-[#f0efec] px-1.5 py-0.5 text-[11px] text-[#52514e]">{{ $t('products.hidden') }}</span>
                                        @endunless
                                    </a>
                                </td>
                                <td class="px-3 py-2 text-end">{{ $num($p['views']) }}</td>
                                <td class="px-3 py-2 text-end">{{ $num($p['viewers']) }}</td>
                                <td class="px-3 py-2 text-end">{{ $num($p['carts']) }}</td>
                                <td class="px-3 py-2 text-end">{{ $p['cartRate'] === null ? '—' : $p['cartRate'].'%' }}</td>
                                <td class="px-3 py-2 text-end">{{ $num($p['units']) }}</td>
                                <td class="ps-3 py-2 text-end">{{ $p['revenue'] > 0 ? format_kwd($p['revenue']) : '—' }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </section>

    {{-- Colors & sizes --}}
    <section class="grid grid-cols-1 gap-6 md:grid-cols-2">
        @foreach(['colors' => 'choices.colors', 'sizes' => 'choices.sizes'] as $key => $title)
            <div class="min-w-0 rounded-lg border border-[#e8e4dc] bg-white p-5">
                <div class="flex flex-wrap items-center justify-between gap-2">
                    <h2 class="m-0 text-lg">{{ $t($title) }}</h2>
                    <div class="flex gap-3 text-xs text-[#52514e]">
                        <span class="inline-flex items-center gap-1.5"><span class="inline-block h-2.5 w-2.5 rounded-sm bg-[#2a78d6]"></span>{{ $t('choices.carts') }}</span>
                        <span class="inline-flex items-center gap-1.5"><span class="inline-block h-2.5 w-2.5 rounded-sm bg-[#eb6834]"></span>{{ $t('choices.ordered') }}</span>
                    </div>
                </div>
                @if(empty($report[$key]))
                    <p class="mt-4 mb-0 text-sm text-[#898781]">{{ $t('choices.empty') }}</p>
                @else
                    @php $max = $maxChoice($report[$key]); @endphp
                    <ul class="m-0 mt-4 flex list-none flex-col gap-3 p-0">
                        @foreach($report[$key] as $row)
                            <li>
                                <div class="flex items-baseline justify-between gap-2 text-sm">
                                    <span>{{ $row['label'] }}</span>
                                    <span class="tabular-nums text-[#52514e]">{{ $row['carts'] }} · {{ $row['ordered'] }}</span>
                                </div>
                                <div class="mt-1.5 flex h-2 gap-0.5 rounded-full bg-[#f0efec]">
                                    @if($row['carts'])<div class="h-2 rounded-full bg-[#2a78d6]" style="width: {{ $row['carts'] / $max * 100 }}%"></div>@endif
                                    @if($row['ordered'])<div class="h-2 rounded-full bg-[#eb6834]" style="width: {{ $row['ordered'] / $max * 100 }}%"></div>@endif
                                </div>
                            </li>
                        @endforeach
                    </ul>
                @endif
            </div>
        @endforeach
    </section>

    {{-- Sources, devices, languages --}}
    <section class="grid grid-cols-1 gap-6 md:grid-cols-3">
        @foreach(['sources' => 'sources', 'devices' => 'devices', 'locales' => 'locales'] as $key => $group)
            <div class="min-w-0 rounded-lg border border-[#e8e4dc] bg-white p-5">
                <h2 class="m-0 text-lg">{{ $t($group.'.title') }}</h2>
                @if(empty($report[$key]))
                    <p class="mt-4 mb-0 text-sm text-[#898781]">{{ $t('none') }}</p>
                @else
                    <ul class="m-0 mt-4 flex list-none flex-col gap-3 p-0">
                        @foreach($report[$key] as $row)
                            @php $nameKey = 'admin.analytics.'.$group.'.names.'.$row['label']; $name = lozan_t($nameKey); @endphp
                            <li>
                                <div class="flex items-baseline justify-between gap-2 text-sm">
                                    <span>{{ $name === $nameKey ? ucfirst($row['label']) : $name }}</span>
                                    <span class="tabular-nums"><strong>{{ $num($row['count']) }}</strong> <span class="text-[#898781]">· {{ $row['percent'] }}%</span></span>
                                </div>
                                <div class="mt-1.5 h-2 rounded-full bg-[#f0efec]">
                                    <div class="h-2 rounded-full bg-[#2a78d6]" style="width: {{ max(2, $row['percent']) }}%"></div>
                                </div>
                            </li>
                        @endforeach
                    </ul>
                @endif
            </div>
        @endforeach
    </section>

    {{-- Customers --}}
    <section class="min-w-0 rounded-lg border border-[#e8e4dc] bg-white p-5">
        <h2 class="m-0 text-lg">{{ $t('customers.title') }}</h2>
        <p class="mt-1 mb-0 text-xs text-[#898781]">{{ $t('customers.note') }}</p>
        @if(empty($report['customers']))
            <p class="mt-4 mb-0 text-sm text-[#898781]">{{ $t('customers.empty') }}</p>
        @else
            <div class="mt-4 overflow-x-auto">
                <table class="w-full min-w-[36rem] border-collapse text-sm tabular-nums">
                    <thead>
                        <tr class="border-b border-[#e8e4dc] text-xs text-[#52514e]">
                            <th class="py-2 pe-3 text-start font-medium">{{ $t('customers.name') }}</th>
                            <th class="px-3 py-2 text-start font-medium">{{ $t('customers.phone') }}</th>
                            <th class="px-3 py-2 text-end font-medium">{{ $t('customers.orders') }}</th>
                            <th class="px-3 py-2 text-end font-medium">{{ $t('customers.total') }}</th>
                            <th class="ps-3 py-2 text-end font-medium">{{ $t('customers.lastOrder') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($report['customers'] as $c)
                            <tr class="border-b border-[#e8e4dc] last:border-0">
                                <td class="py-2 pe-3">{{ $c['name'] }}</td>
                                <td class="px-3 py-2"><a href="tel:{{ $c['phone'] }}" dir="ltr" class="text-inherit">{{ $c['phone'] }}</a></td>
                                <td class="px-3 py-2 text-end">{{ $c['orders'] }}</td>
                                <td class="px-3 py-2 text-end">{{ format_kwd($c['total']) }}</td>
                                <td class="ps-3 py-2 text-end">{{ $c['lastOrder'] }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </section>

    <p class="m-0 text-xs text-[#898781]">{{ $t('privacy') }}</p>
</div>

@php
    $chartData = [
        'rtl' => $isRtl,
        'daily' => $report['daily'],
        'hours' => $report['hours'],
        'products' => array_map(fn ($p) => ['name' => $p['name'], 'views' => $p['views']], $topProducts),
        'labels' => [
            'visitors' => $t('trend.visitors'),
            'productViews' => $t('trend.productViews'),
            'views' => $t('hours.views'),
            'productViewsSingle' => $t('top.views'),
        ],
    ];
@endphp
<script type="application/json" id="analytics-data">@json($chartData)</script>
@endsection

@push('scripts')
    <script src="/js/vendor/chart.umd.min.js"></script>
    <script src="/js/admin-analytics.js?v={{ filemtime(public_path('js/admin-analytics.js')) }}"></script>
@endpush
