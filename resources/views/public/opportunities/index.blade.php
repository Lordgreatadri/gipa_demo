<x-public-layout title="Investment opportunities" description="Search and filter verified investment opportunities across Ghana's regions and districts.">
    <section class="directory-intro">
        <div class="shell directory-intro__inner">
            <div><p class="eyebrow"><span></span> National investment pipeline</p><h1>Find your next opportunity in Ghana.</h1><p>Search verified projects by location, sector, enterprise type and lifecycle status.</p></div>
            <div class="directory-intro__metric"><strong>{{ number_format($opportunities->total()) }}</strong><span>{{ Str::plural('opportunity', $opportunities->total()) }} found</span></div>
        </div>
    </section>

    <section class="directory shell" aria-label="Opportunity directory">
        <button class="filter-disclosure" type="button" data-filter-toggle aria-expanded="false" aria-controls="opportunity-filters"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M4 6h16M7 12h10M10 18h4"></path></svg> Filters</button>
        <aside class="filter-panel" id="opportunity-filters" data-filter-panel>
            <div class="filter-panel__heading"><div><p class="eyebrow eyebrow--dark">Refine results</p><h2>Advanced filters</h2></div><a href="{{ route('opportunities.index') }}">Clear all</a></div>
            <form action="{{ route('opportunities.index') }}" method="get" data-opportunity-filters>
                <label class="field field--wide"><span>Keyword</span><div class="input-with-icon"><svg viewBox="0 0 24 24" aria-hidden="true"><circle cx="11" cy="11" r="7"></circle><path d="m20 20-4-4"></path></svg><input type="search" name="query" value="{{ request('query') }}" placeholder="Project, district or sector"></div></label>
                <label class="field"><span>Region</span><select name="region" data-region-select><option value="">All regions</option>@foreach($filters['regions'] as $region)<option value="{{ $region->uuid }}" @selected(request('region') === $region->uuid)>{{ $region->name }}</option>@endforeach</select></label>
                <label class="field"><span>District</span><select name="district" data-district-select><option value="">All districts</option>@foreach($filters['districts'] as $district)<option value="{{ $district->uuid }}" data-region="{{ $district->region->uuid }}" @selected(request('district') === $district->uuid)>{{ $district->name }}</option>@endforeach</select></label>
                <label class="field"><span>Sector</span><select name="sector"><option value="">All sectors</option>@foreach($filters['sectors'] as $sector)<option value="{{ $sector->uuid }}" @selected(request('sector') === $sector->uuid)>{{ $sector->name }}</option>@endforeach</select></label>
                <label class="field"><span>Enterprise type</span><select name="type"><option value="">All types</option>@foreach($filters['types'] as $type)<option value="{{ $type->uuid }}" @selected(request('type') === $type->uuid)>{{ $type->name }}</option>@endforeach</select></label>
                {{-- <label class="field"><span>Status</span><select name="status"><option value="">All public statuses</option>@foreach($filters['statuses'] as $value => $label)<option value="{{ $value }}" @selected(request('status') === $value)>{{ $label }}</option>@endforeach</select></label> --}}
                <label class="field"><span>Sort by</span><select name="sort"><option value="newest">Recently published</option><option value="amount_desc" @selected(request('sort') === 'amount_desc')>Investment: high to low</option><option value="amount_asc" @selected(request('sort') === 'amount_asc')>Investment: low to high</option></select></label>
                <button class="button button--gold filter-panel__submit" type="submit">Apply filters</button>
            </form>
        </aside>

        <div class="results-panel">
            <div class="results-toolbar"><p><strong>{{ number_format($opportunities->total()) }}</strong> verified {{ Str::plural('result', $opportunities->total()) }}</p>@if(request()->hasAny(['query','region','district','sector','type','status']))<a href="{{ route('opportunities.index') }}">Reset search</a>@endif</div>
            @if($opportunities->isEmpty())
                <div class="empty-state"><span aria-hidden="true">0</span><h2>No opportunities match these filters</h2><p>Try broadening the location, sector or status criteria.</p><a class="button button--outline" href="{{ route('opportunities.index') }}">View all opportunities</a></div>
            @else
                <div class="results-grid">@foreach($opportunities as $opportunity)<x-opportunity-card :opportunity="$opportunity" />@endforeach</div>
                @if($opportunities->hasPages())
                    <nav class="directory-pagination" aria-label="Opportunity result pages">
                        @if($opportunities->onFirstPage())<span aria-disabled="true">Previous</span>@else<a href="{{ $opportunities->previousPageUrl() }}" rel="prev">Previous</a>@endif
                        <strong>Page {{ $opportunities->currentPage() }} of {{ $opportunities->lastPage() }}</strong>
                        @if($opportunities->hasMorePages())<a href="{{ $opportunities->nextPageUrl() }}" rel="next">Next</a>@else<span aria-disabled="true">Next</span>@endif
                    </nav>
                @endif
            @endif
        </div>
    </section>
</x-public-layout>