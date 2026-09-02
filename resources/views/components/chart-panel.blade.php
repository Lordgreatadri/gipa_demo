@props(['title', 'description', 'type', 'labels', 'datasets', 'summary', 'wide' => false])

<article @class(['chart-panel', 'chart-panel--wide' => $wide]) data-chart-panel>
    <header><div><h2>{{ $title }}</h2><p>{{ $description }}</p></div><i data-lucide="chart-no-axes-combined" aria-hidden="true"></i></header>
    <div class="chart-panel__canvas"><canvas data-dashboard-chart aria-label="{{ $summary }}" role="img"></canvas></div>
    <p class="sr-only">{{ $summary }}</p>
    <script type="application/json" data-chart-config>@json(['type' => $type, 'labels' => $labels, 'datasets' => $datasets])</script>
</article>