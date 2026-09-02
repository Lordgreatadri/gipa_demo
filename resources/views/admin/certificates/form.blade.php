<x-admin-layout title="Prepare certificate">
    <nav class="admin-breadcrumb" aria-label="Breadcrumb"><a href="{{ route('staff.certificates.index') }}">Certificates</a><span>/</span><span>New draft</span></nav>
    <div class="admin-page-heading"><div><p class="admin-kicker">Controlled issuance</p><h1>Prepare certificate</h1><p>Capture the legal display snapshot. It becomes immutable after issuance.</p></div></div>
    <form class="record-form" method="post" action="{{ route('staff.certificates.store') }}">@csrf
        <section><div class="record-form__heading"><h2>Classification</h2><p>Select the approved type and responsible district.</p></div><div class="record-form__grid">
            <label class="field"><span>Certificate type</span><select name="certificate_type" required><option value="">Select type</option>@foreach($types as $type)<option value="{{ $type->uuid }}" @selected(old('certificate_type')===$type->uuid)>{{ $type->name }}</option>@endforeach</select>@error('certificate_type')<small class="field-error">{{ $message }}</small>@enderror</label>
            <label class="field"><span>District</span><select name="district" required><option value="">Select district</option>@foreach($districts as $district)<option value="{{ $district->uuid }}" @selected(old('district')===$district->uuid)>{{ $district->name }}</option>@endforeach</select>@error('district')<small class="field-error">{{ $message }}</small>@enderror</label>
            <label class="field field--wide"><span>Investment opportunity</span><select name="opportunity"><option value="">Not linked</option>@foreach($opportunities as $opportunity)<option value="{{ $opportunity->uuid }}" @selected(old('opportunity')===$opportunity->uuid)>{{ $opportunity->title }}</option>@endforeach</select></label>
        </div></section>
        <section><div class="record-form__heading"><h2>Issued snapshot</h2><p>Use the authoritative legal names shown on the certificate.</p></div><div class="record-form__grid">
            <label class="field field--wide"><span>Holder name</span><input name="holder_name" value="{{ old('holder_name') }}" maxlength="255" required>@error('holder_name')<small class="field-error">{{ $message }}</small>@enderror</label>
            <label class="field"><span>Organization name</span><input name="organization_name" value="{{ old('organization_name') }}" maxlength="255"></label>
            <label class="field"><span>Project name</span><input name="project_name" value="{{ old('project_name') }}" maxlength="255"></label>
            <label class="field"><span>Custom expiry date</span><input type="date" name="expires_at" value="{{ old('expires_at') }}"><small>Leave blank to use the certificate type default.</small></label>
        </div></section>
        <div class="record-form__actions"><a class="button button--outline" href="{{ route('staff.certificates.index') }}">Cancel</a><button class="button button--gold" type="submit">Create draft</button></div>
    </form>
</x-admin-layout>