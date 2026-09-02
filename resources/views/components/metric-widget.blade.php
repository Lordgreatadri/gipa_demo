@props(['label', 'value', 'note', 'icon', 'tone' => 'green', 'trend' => null])

<article class="metric-widget metric-widget--{{ $tone }}">
    <div class="metric-widget__top"><span>{{ $label }}</span><i data-lucide="{{ $icon }}" aria-hidden="true"></i></div>
    <strong>{{ $value }}</strong>
    <div class="metric-widget__foot"><small>{{ $note }}</small>@if($trend)<b>{{ $trend }}</b>@endif</div>
</article>