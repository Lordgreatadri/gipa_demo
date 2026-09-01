<x-admin-layout :title="$district->exists ? 'Edit district' : 'Add district'">
    <nav class="admin-breadcrumb" aria-label="Breadcrumb"><a href="{{ route('staff.districts.index') }}">Districts</a><span>/</span><span>{{ $district->exists ? 'Edit draft' : 'New district' }}</span></nav>
    <div class="admin-page-heading"><div><p class="admin-kicker">District registry</p><h1>{{ $district->exists ? 'Edit district draft' : 'Add a district' }}</h1><p>Record the district profile and readiness indicators before submitting it for review.</p></div></div>
    <form class="record-form" method="post" action="{{ $district->exists ? route('staff.districts.update', $district) : route('staff.districts.store') }}">
        @csrf @if($district->exists) @method('PUT') @endif
        <section>
            <div class="record-form__heading"><h2>District identity</h2><p>Core registry and location details.</p></div>
            <div class="record-form__grid">
                <label class="field"><span>Region</span><select name="region" required><option value="">Select region</option>@foreach($regions as $region)<option value="{{ $region->uuid }}" @selected(old('region', $district->region?->uuid) === $region->uuid)>{{ $region->name }}</option>@endforeach</select></label>
                <label class="field"><span>District code</span><input name="code" value="{{ old('code', $district->code) }}" maxlength="24" required></label>
                <label class="field"><span>District name</span><input name="name" value="{{ old('name', $district->name) }}" required></label>
                <label class="field"><span>Capital</span><input name="capital" value="{{ old('capital', $district->capital) }}"></label>
                <label class="field field--wide"><span>Location description</span><textarea name="location_description" rows="4">{{ old('location_description', $district->location_description) }}</textarea></label>
            </div>
        </section>
        <section>
            <div class="record-form__heading"><h2>District indicators</h2><p>Use the latest verified figures available.</p></div>
            <div class="record-form__grid">
                <label class="field"><span>Population</span><input type="number" min="0" name="population" value="{{ old('population', $district->population) }}"></label>
                <label class="field"><span>Area (km²)</span><input type="number" min="0" step="0.01" name="area_sq_km" value="{{ old('area_sq_km', $district->area_sq_km) }}"></label>
                <label class="field"><span>Readiness score (%)</span><input type="number" min="0" max="100" step="0.01" name="readiness_score" value="{{ old('readiness_score', $district->readiness_score) }}"></label>
                <label class="field"><span>Infrastructure score (%)</span><input type="number" min="0" max="100" step="0.01" name="infrastructure_quality_score" value="{{ old('infrastructure_quality_score', $district->infrastructure_quality_score) }}"></label>
            </div>
        </section>
        <div class="record-form__actions"><a class="button button--outline" href="{{ $district->exists ? route('staff.districts.show', $district) : route('staff.districts.index') }}">Cancel</a><button class="button button--gold" type="submit">{{ $district->exists ? 'Save changes' : 'Create district draft' }}</button></div>
    </form>
</x-admin-layout>
