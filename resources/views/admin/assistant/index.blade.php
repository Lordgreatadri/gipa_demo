<x-admin-layout title="Assistant knowledge base">
    <div class="admin-page-heading">
        <div>
            <p class="admin-kicker">GIPA Assistant</p>
            <h1>Knowledge base</h1>
            <p>Curate the institutional content the assistant is allowed to answer from. Every published document is chunked and indexed for retrieval.</p>
        </div>
        @if($canManage)
            <div class="admin-page-heading__actions">
                <form method="post" action="{{ route('staff.assistant.knowledge.reindex-all') }}">
                    @csrf
                    <button class="button button--outline" type="submit">Re-index all</button>
                </form>
                <a class="button button--gold" href="{{ route('staff.assistant.knowledge.create') }}">Add document</a>
            </div>
        @endif
    </div>

    <section class="metric-widget-grid" aria-label="Knowledge base overview">
        <x-metric-widget label="Documents" :value="number_format($stats['documents'])" note="Total in knowledge base" icon="book-open" tone="green" />
        <x-metric-widget label="Published" :value="number_format($stats['published'])" note="Answerable by the assistant" icon="check-circle" tone="blue" />
        <x-metric-widget label="Indexed chunks" :value="number_format($stats['chunks'])" note="Retrievable passages" icon="layers" tone="gold" />
        <x-metric-widget label="Conversations" :value="number_format($stats['conversations'])" note="Assistant sessions logged" icon="messages-square" tone="green" />
    </section>

    @if($stats['pending'] > 0)
        <div class="admin-alert admin-alert--error" role="status">
            <strong>{{ $stats['pending'] }} document(s) need re-indexing.</strong>
            <span>Their content changed since they were last indexed. Re-index them so the assistant uses the latest wording.</span>
        </div>
    @endif

    @if($reindexStatus)
        @php($reindexState = $reindexStatus['state'] ?? 'unknown')
        <div class="admin-alert {{ $reindexState === 'failed' ? 'admin-alert--error' : '' }}" role="status">
            @switch($reindexState)
                @case('queued')
                    <strong>Full re-index queued.</strong>
                    <span>The knowledge base will be rebuilt by a background worker shortly.</span>
                    @break
                @case('running')
                    <strong>Full re-index in progress…</strong>
                    <span>A background worker is rebuilding the knowledge index. Refresh to see the result.</span>
                    @break
                @case('completed')
                    <strong>Last full re-index completed.</strong>
                    <span>Rebuilt {{ number_format($reindexStatus['chunks'] ?? 0) }} chunks{{ isset($reindexStatus['finished_at']) ? ' · '.\Illuminate\Support\Carbon::parse($reindexStatus['finished_at'])->diffForHumans() : '' }}.</span>
                    @break
                @case('failed')
                    <strong>The last full re-index failed.</strong>
                    <span>{{ $reindexStatus['error'] ?? 'An unexpected error occurred.' }} Please retry once the cause is resolved.</span>
                    @break
            @endswitch
        </div>
    @endif

    <section class="reference-section">
        <div class="reference-section__heading">
            <div><h2>Documents</h2><p>Published documents are retrieved and cited when they match a question.</p></div>
            <strong>{{ number_format($documents->count()) }}</strong>
        </div>

        <div class="reference-list">
            @forelse($documents as $document)
                <details class="reference-row">
                    <summary>
                        <span>
                            <strong>{{ $document->title }}</strong>
                            <small>{{ $categories[$document->category] ?? $document->category }} · {{ $document->chunks_count }} chunks · updated {{ $document->updated_at?->diffForHumans() }}</small>
                        </span>
                        <span class="admin-status">{{ $document->is_published ? 'Published' : 'Draft' }}@if($document->needsIndexing()) · needs indexing @endif</span>
                    </summary>
                    <div class="reference-edit">
                        @if($document->summary)<p>{{ $document->summary }}</p>@endif
                        <p class="assistant-doc-preview">{{ \Illuminate\Support\Str::limit($document->body, 320) }}</p>
                        @if($canManage)
                            <div class="reference-edit__actions">
                                <a class="button button--outline" href="{{ route('staff.assistant.knowledge.edit', $document) }}">Edit</a>
                                <form method="post" action="{{ route('staff.assistant.knowledge.reindex', $document) }}">
                                    @csrf
                                    <button class="button button--outline" type="submit">Re-index</button>
                                </form>
                                <form method="post" action="{{ route('staff.assistant.knowledge.destroy', $document) }}" onsubmit="return confirm('Remove this document from the knowledge base?')">
                                    @csrf @method('DELETE')
                                    <button class="button button--danger" type="submit">Delete</button>
                                </form>
                            </div>
                        @endif
                    </div>
                </details>
            @empty
                <p class="admin-empty">No knowledge documents yet. Add your first document to give the assistant something to answer from.</p>
            @endforelse
        </div>
    </section>
</x-admin-layout>
