<x-admin-layout title="Opportunity reference data">
    <div class="admin-page-heading">
        <div><p class="admin-kicker">Opportunity configuration</p><h1>{{ $section ? str($section)->replace('-', ' ')->title() : 'Reference data overview' }}</h1><p>Manage the classifications available when staff create and maintain investment opportunities.</p></div>
        <a class="button button--gold" href="{{ route('staff.opportunities.create') }}">Create opportunity</a>
    </div>

    @if($section === null)
        <section class="metric-widget-grid" aria-label="Reference data overview">
            <x-metric-widget label="Sectors" :value="number_format($sectors->count())" note="Primary classifications" icon="factory" tone="green" />
            <x-metric-widget label="Sub-sectors" :value="number_format($subSectors->count())" note="Detailed classifications" icon="git-branch" tone="blue" />
            <x-metric-widget label="Enterprise types" :value="number_format($enterpriseTypes->count())" note="Operating structures" icon="building-2" tone="gold" />
        </section>
    @endif

    @if($section === 'sectors')
    <section class="reference-section" id="sectors">
        <div class="reference-section__heading"><div><h2>Sectors</h2><p>Primary economic classifications used for filtering and reporting.</p></div><strong>{{ number_format($sectors->count()) }}</strong></div>
        <form class="reference-create" method="post" action="{{ route('staff.reference-data.store', 'sector') }}">
            @csrf
            <label class="field"><span>Code</span><input name="code" maxlength="32" required></label>
            <label class="field"><span>Sector name</span><input name="name" required></label>
            <label class="field"><span>Sort order</span><input type="number" name="sort_order" min="0" value="0"></label>
            <label class="field reference-create__description"><span>Description</span><input name="description"></label>
            <input type="hidden" name="is_active" value="1">
            <button class="button button--gold" type="submit">Add sector</button>
        </form>
        <div class="reference-list">
            @foreach($sectors as $sector)
                <details class="reference-row">
                    <summary><span><strong>{{ $sector->name }}</strong><small>{{ $sector->code }} · {{ $sector->sub_sectors_count }} sub-sectors · {{ $sector->opportunities_count }} opportunities</small></span><span class="admin-status">{{ $sector->is_active ? 'Active' : 'Inactive' }}</span></summary>
                    <form class="reference-edit" method="post" action="{{ route('staff.reference-data.update', ['sector', $sector->uuid]) }}">
                        @csrf @method('PUT')
                        <label class="field"><span>Code</span><input name="code" value="{{ $sector->code }}" required></label>
                        <label class="field"><span>Name</span><input name="name" value="{{ $sector->name }}" required></label>
                        <label class="field"><span>Sort order</span><input type="number" min="0" name="sort_order" value="{{ $sector->sort_order }}"></label>
                        <label class="field field--wide"><span>Description</span><textarea name="description" rows="2">{{ $sector->description }}</textarea></label>
                        <label class="consent-field"><input type="checkbox" name="is_active" value="1" @checked($sector->is_active)><span>Available for selection</span></label>
                        <div class="reference-edit__actions"><button class="button button--outline" type="submit">Save</button></div>
                    </form>
                    <form class="reference-delete" method="post" action="{{ route('staff.reference-data.destroy', ['sector', $sector->uuid]) }}" onsubmit="return confirm('Delete this unused sector?')">@csrf @method('DELETE')<button type="submit">Delete unused sector</button></form>
                </details>
            @endforeach
        </div>
    </section>
    @endif

    @if($section === 'sub-sectors')
    <section class="reference-section" id="sub-sectors">
        <div class="reference-section__heading"><div><h2>Sub-sectors</h2><p>Detailed classifications linked to a parent sector.</p></div><strong>{{ number_format($subSectors->count()) }}</strong></div>
        <form class="reference-create" method="post" action="{{ route('staff.reference-data.store', 'sub-sector') }}">
            @csrf
            <label class="field"><span>Parent sector</span><select name="sector" required><option value="">Select sector</option>@foreach($sectors as $sector)<option value="{{ $sector->uuid }}">{{ $sector->name }}</option>@endforeach</select></label>
            <label class="field"><span>Code</span><input name="code" maxlength="32" required></label>
            <label class="field"><span>Sub-sector name</span><input name="name" required></label>
            <label class="field"><span>Sort order</span><input type="number" name="sort_order" min="0" value="0"></label>
            <input type="hidden" name="is_active" value="1">
            <button class="button button--gold" type="submit">Add sub-sector</button>
        </form>
        <div class="reference-list">
            @foreach($subSectors as $subSector)
                <details class="reference-row">
                    <summary><span><strong>{{ $subSector->name }}</strong><small>{{ $subSector->code }} · {{ $subSector->sector->name }} · {{ $subSector->opportunities_count }} opportunities</small></span><span class="admin-status">{{ $subSector->is_active ? 'Active' : 'Inactive' }}</span></summary>
                    <form class="reference-edit" method="post" action="{{ route('staff.reference-data.update', ['sub-sector', $subSector->uuid]) }}">
                        @csrf @method('PUT')
                        <label class="field"><span>Parent sector</span><select name="sector" required>@foreach($sectors as $sector)<option value="{{ $sector->uuid }}" @selected($subSector->sector_id === $sector->id)>{{ $sector->name }}</option>@endforeach</select></label>
                        <label class="field"><span>Code</span><input name="code" value="{{ $subSector->code }}" required></label>
                        <label class="field"><span>Name</span><input name="name" value="{{ $subSector->name }}" required></label>
                        <label class="field"><span>Sort order</span><input type="number" min="0" name="sort_order" value="{{ $subSector->sort_order }}"></label>
                        <label class="field field--wide"><span>Description</span><textarea name="description" rows="2">{{ $subSector->description }}</textarea></label>
                        <label class="consent-field"><input type="checkbox" name="is_active" value="1" @checked($subSector->is_active)><span>Available for selection</span></label>
                        <div class="reference-edit__actions"><button class="button button--outline" type="submit">Save</button></div>
                    </form>
                    <form class="reference-delete" method="post" action="{{ route('staff.reference-data.destroy', ['sub-sector', $subSector->uuid]) }}" onsubmit="return confirm('Delete this unused sub-sector?')">@csrf @method('DELETE')<button type="submit">Delete unused sub-sector</button></form>
                </details>
            @endforeach
        </div>
    </section>
    @endif

    @if($section === 'enterprise-types')
    <section class="reference-section" id="enterprise-types">
        <div class="reference-section__heading"><div><h2>Enterprise types</h2><p>Legal and operating structures used to describe opportunity sponsors.</p></div><strong>{{ number_format($enterpriseTypes->count()) }}</strong></div>
        <form class="reference-create" method="post" action="{{ route('staff.reference-data.store', 'enterprise-type') }}">
            @csrf
            <label class="field"><span>Code</span><input name="code" maxlength="32" required></label>
            <label class="field"><span>Enterprise type</span><input name="name" required></label>
            <label class="field reference-create__description"><span>Description</span><input name="description"></label>
            <input type="hidden" name="is_active" value="1">
            <button class="button button--gold" type="submit">Add enterprise type</button>
        </form>
        <div class="reference-list">
            @foreach($enterpriseTypes as $type)
                <details class="reference-row">
                    <summary><span><strong>{{ $type->name }}</strong><small>{{ $type->code }} · {{ $type->opportunities_count }} opportunities</small></span><span class="admin-status">{{ $type->is_active ? 'Active' : 'Inactive' }}</span></summary>
                    <form class="reference-edit" method="post" action="{{ route('staff.reference-data.update', ['enterprise-type', $type->uuid]) }}">
                        @csrf @method('PUT')
                        <label class="field"><span>Code</span><input name="code" value="{{ $type->code }}" required></label>
                        <label class="field"><span>Name</span><input name="name" value="{{ $type->name }}" required></label>
                        <label class="field field--wide"><span>Description</span><textarea name="description" rows="2">{{ $type->description }}</textarea></label>
                        <label class="consent-field"><input type="checkbox" name="is_active" value="1" @checked($type->is_active)><span>Available for selection</span></label>
                        <div class="reference-edit__actions"><button class="button button--outline" type="submit">Save</button></div>
                    </form>
                    <form class="reference-delete" method="post" action="{{ route('staff.reference-data.destroy', ['enterprise-type', $type->uuid]) }}" onsubmit="return confirm('Delete this unused enterprise type?')">@csrf @method('DELETE')<button type="submit">Delete unused enterprise type</button></form>
                </details>
            @endforeach
        </div>
    </section>
    @endif
</x-admin-layout>
