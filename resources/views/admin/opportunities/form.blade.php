<x-admin-layout :title="$opportunity->exists ? 'Edit opportunity' : 'Add opportunity'">
    <nav class="admin-breadcrumb" aria-label="Breadcrumb"><a href="{{ route('staff.opportunities.index') }}">Opportunities</a><span>/</span><span>{{ $opportunity->exists ? 'Edit draft' : 'New opportunity' }}</span></nav>
    <div class="admin-page-heading"><div><p class="admin-kicker">Investment pipeline</p><h1>{{ $opportunity->exists ? 'Edit opportunity draft' : 'Add an opportunity' }}</h1><p>Capture the core investment case before assigning the record for review.</p></div><a class="button button--outline" href="{{ route('staff.reference-data.index') }}">Manage reference data</a></div>
    <form class="record-form" method="post" action="{{ $opportunity->exists ? route('staff.opportunities.update', $opportunity) : route('staff.opportunities.store') }}">
        @csrf @if($opportunity->exists) @method('PUT') @endif
        <section>
            <div class="record-form__heading"><h2>Classification</h2><p>Place the opportunity in the correct district and investment category.</p></div>
            <div class="record-form__grid">
                <label class="field"><span>District</span><select name="district" required><option value="">Select district</option>@foreach($districts as $district)<option value="{{ $district->uuid }}" @selected(old('district', $opportunity->district?->uuid) === $district->uuid)>{{ $district->name }}</option>@endforeach</select></label>
                <label class="field"><span>Sector</span><select name="sector" required data-sector-select><option value="">Select sector</option>@foreach($sectors as $sector)<option value="{{ $sector->uuid }}" @selected(old('sector', $opportunity->sector?->uuid) === $sector->uuid)>{{ $sector->name }}</option>@endforeach</select></label>
                <label class="field"><span>Sub-sector</span><select name="sub_sector" data-sub-sector-select><option value="">No sub-sector</option>@foreach($subSectors as $subSector)<option value="{{ $subSector->uuid }}" data-sector="{{ $subSector->sector_uuid }}" @selected(old('sub_sector', $opportunity->subSector?->uuid) === $subSector->uuid)>{{ $subSector->name }}</option>@endforeach</select></label>
                <label class="field"><span>Enterprise type</span><select name="enterprise_type" required><option value="">Select enterprise type</option>@foreach($enterpriseTypes as $type)<option value="{{ $type->uuid }}" @selected(old('enterprise_type', $opportunity->enterpriseType?->uuid) === $type->uuid)>{{ $type->name }}</option>@endforeach</select></label>
            </div>
        </section>
        <section>
            <div class="record-form__heading"><h2>Investment case</h2><p>Describe the proposition with decision-ready evidence.</p></div>
            <div class="record-form__grid">
                <label class="field field--wide"><span>Opportunity title</span><input name="title" value="{{ old('title', $opportunity->title) }}" required></label>
                <label class="field field--wide"><span>Overview</span><textarea name="overview" rows="6" required>{{ old('overview', $opportunity->overview) }}</textarea></label>
                <label class="field"><span>Location description</span><textarea name="location_description" rows="4">{{ old('location_description', $opportunity->location_description) }}</textarea></label>
                <label class="field"><span>Objectives</span><textarea name="objectives" rows="4">{{ old('objectives', $opportunity->objectives) }}</textarea></label>
                <label class="field"><span>Investment rationale</span><textarea name="rationale" rows="4">{{ old('rationale', $opportunity->rationale) }}</textarea></label>
                <label class="field"><span>Success factors</span><textarea name="success_factors" rows="4">{{ old('success_factors', $opportunity->success_factors) }}</textarea></label>
                <label class="field field--wide"><span>Competitive advantages</span><textarea name="competitive_advantages" rows="4">{{ old('competitive_advantages', $opportunity->competitive_advantages) }}</textarea></label>
            </div>
        </section>
        <section>
            <div class="record-form__heading"><h2>Map coordinates</h2><p>Optional geographic point for accurate public mapping.</p></div>
            <div class="record-form__grid">
                <label class="field"><span>Latitude</span><input type="number" min="-90" max="90" step="0.0000001" name="latitude" value="{{ old('latitude', $opportunity->latitude) }}"></label>
                <label class="field"><span>Longitude</span><input type="number" min="-180" max="180" step="0.0000001" name="longitude" value="{{ old('longitude', $opportunity->longitude) }}"></label>
            </div>
        </section>
        <div class="record-form__actions"><a class="button button--outline" href="{{ $opportunity->exists ? route('staff.opportunities.show', $opportunity) : route('staff.opportunities.index') }}">Cancel</a><button class="button button--gold" type="submit">{{ $opportunity->exists ? 'Save changes' : 'Create opportunity draft' }}</button></div>
    </form>
</x-admin-layout>
