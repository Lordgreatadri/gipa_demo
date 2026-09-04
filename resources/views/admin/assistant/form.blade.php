@php($isEdit = $document->exists)
<x-admin-layout :title="$isEdit ? 'Edit knowledge document' : 'Add knowledge document'">
    <nav class="admin-breadcrumb" aria-label="Breadcrumb">
        <a href="{{ route('staff.assistant.knowledge.index') }}">Knowledge base</a>
        <span>/</span>
        <span>{{ $isEdit ? 'Edit document' : 'New document' }}</span>
    </nav>

    <div class="admin-page-heading">
        <div>
            <p class="admin-kicker">GIPA Assistant</p>
            <h1>{{ $isEdit ? 'Edit knowledge document' : 'Add knowledge document' }}</h1>
            <p>Write clear, factual institutional content. The assistant answers only from published documents and cites them by title.</p>
        </div>
        <a class="button button--outline" href="{{ route('staff.assistant.knowledge.index') }}">Back to knowledge base</a>
    </div>

    @if($errors->any())
        <div class="admin-alert admin-alert--error" role="alert">
            <strong>This document could not be saved.</strong>
            <span>Please correct the highlighted fields and try again.</span>
        </div>
    @endif

    <form class="record-form" method="post" action="{{ $isEdit ? route('staff.assistant.knowledge.update', $document) : route('staff.assistant.knowledge.store') }}">
        @csrf
        @if($isEdit) @method('PUT') @endif

        <section>
            <div class="record-form__heading">
                <h2>Document details</h2>
                <p>Classify the document and control whether the assistant may answer from it.</p>
            </div>
            <div class="record-form__grid">
                <label class="field field--wide">
                    <span>Title</span>
                    <input name="title" value="{{ old('title', $document->title) }}" maxlength="180" placeholder="e.g. Investor onboarding and KYC" required>
                    @error('title')<small class="field-error">{{ $message }}</small>@enderror
                </label>

                <label class="field">
                    <span>Category</span>
                    <select name="category" required>
                        @foreach($categories as $value => $label)
                            <option value="{{ $value }}" @selected(old('category', $document->category) === $value)>{{ $label }}</option>
                        @endforeach
                    </select>
                    @error('category')<small class="field-error">{{ $message }}</small>@enderror
                </label>

                <label class="field">
                    <span>Summary <small>optional</small></span>
                    <input name="summary" value="{{ old('summary', $document->summary) }}" maxlength="280" placeholder="Short internal note shown to staff only">
                    @error('summary')<small class="field-error">{{ $message }}</small>@enderror
                </label>

                <div class="field field--wide">
                    <span>Visibility</span>
                    <label class="consent-field">
                        <input type="checkbox" name="is_published" value="1" @checked(old('is_published', $document->is_published))>
                        <span>Published — allow the assistant to answer from this document and cite it publicly. Leave unchecked to keep it as an internal draft.</span>
                    </label>
                </div>
            </div>
        </section>

        <section>
            <div class="record-form__heading">
                <h2>Content</h2>
                <p>Use plain, factual language. Separate distinct topics with a blank line — each block is indexed and retrieved on its own.</p>
            </div>
            <div class="record-form__grid">
                <label class="field field--wide">
                    <span>Body <small>up to 20,000 characters</small></span>
                    <textarea name="body" rows="18" maxlength="20000" placeholder="Write the institutional content the assistant should rely on…" required>{{ old('body', $document->body) }}</textarea>
                    <small class="field-hint">Content is chunked and re-indexed automatically whenever you save.</small>
                    @error('body')<small class="field-error">{{ $message }}</small>@enderror
                </label>
            </div>
        </section>

        <div class="record-form__actions">
            <a class="button button--outline" href="{{ route('staff.assistant.knowledge.index') }}">Cancel</a>
            <button class="button button--gold" type="submit">{{ $isEdit ? 'Save and re-index' : 'Create and index' }}</button>
        </div>
    </form>
</x-admin-layout>
