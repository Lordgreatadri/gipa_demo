@props(['opportunity'])

<article class="result-card">
    <a class="result-card__visual" href="{{ route('opportunities.show', $opportunity->uuid) }}" aria-label="View {{ $opportunity->title }}">
        <span class="result-card__sector-mark" aria-hidden="true">{{ strtoupper(substr($opportunity->sector->name, 0, 2)) }}</span>
        <span class="status-pill">{{ str($opportunity->workflow_status)->replace('_', ' ')->title() }}</span>
        <span class="result-card__pattern" aria-hidden="true"></span>
    </a>
    <div class="result-card__body">
        <p class="card-meta">{{ $opportunity->sector->name }}@if($opportunity->subSector) <span>/</span> {{ $opportunity->subSector->name }}@endif</p>
        <h2><a href="{{ route('opportunities.show', $opportunity->uuid) }}">{{ $opportunity->title }}</a></h2>
        <p class="result-card__summary">{{ str($opportunity->overview)->stripTags()->limit(130) }}</p>
        <p class="location"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M20 10c0 5-8 11-8 11S4 15 4 10a8 8 0 1 1 16 0Z"></path><circle cx="12" cy="10" r="2.5"></circle></svg>{{ $opportunity->district->name }}, {{ $opportunity->district->region->name }}</p>
        <div class="result-card__footer">
            <div><span>Investment required</span><strong>{{ $opportunity->financial?->amount ? $opportunity->financial->currency.' '.number_format((float) $opportunity->financial->amount) : 'On request' }}</strong></div>
            <span class="result-card__arrow" aria-hidden="true">&rarr;</span>
        </div>
    </div>
</article>