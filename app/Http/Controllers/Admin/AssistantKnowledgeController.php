<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AssistantConversation;
use App\Models\AssistantDocument;
use App\Services\Assistant\KnowledgeIndexer;
use App\Support\AssistantPermissions;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class AssistantKnowledgeController extends Controller
{
    public function __construct(private readonly KnowledgeIndexer $indexer) {}

    public function index(Request $request): View
    {
        abort_unless($request->user()->can(AssistantPermissions::KNOWLEDGE_VIEW), 403);

        $documents = AssistantDocument::query()
            ->withCount('chunks')
            ->orderByDesc('updated_at')
            ->get();

        return view('admin.assistant.index', [
            'documents' => $documents,
            'categories' => AssistantDocument::CATEGORIES,
            'stats' => [
                'documents' => $documents->count(),
                'published' => $documents->where('is_published', true)->count(),
                'chunks' => $documents->sum('chunks_count'),
                'pending' => $documents->filter->needsIndexing()->count(),
                'conversations' => AssistantConversation::query()->count(),
            ],
            'canManage' => $request->user()->can(AssistantPermissions::KNOWLEDGE_MANAGE),
        ]);
    }

    public function create(Request $request): View
    {
        abort_unless($request->user()->can(AssistantPermissions::KNOWLEDGE_MANAGE), 403);

        return view('admin.assistant.form', [
            'document' => new AssistantDocument(['category' => 'faq', 'is_published' => true]),
            'categories' => AssistantDocument::CATEGORIES,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        abort_unless($request->user()->can(AssistantPermissions::KNOWLEDGE_MANAGE), 403);

        $data = $this->validated($request);

        $document = new AssistantDocument($data);
        $document->slug = $this->uniqueSlug($data['title']);
        $document->created_by = $request->user()->id;
        $document->updated_by = $request->user()->id;
        $document->save();

        $this->indexer->index($document);

        return redirect()
            ->route('staff.assistant.knowledge.index')
            ->with('status', 'Knowledge document created and indexed.');
    }

    public function edit(Request $request, AssistantDocument $document): View
    {
        abort_unless($request->user()->can(AssistantPermissions::KNOWLEDGE_MANAGE), 403);

        return view('admin.assistant.form', [
            'document' => $document,
            'categories' => AssistantDocument::CATEGORIES,
        ]);
    }

    public function update(Request $request, AssistantDocument $document): RedirectResponse
    {
        abort_unless($request->user()->can(AssistantPermissions::KNOWLEDGE_MANAGE), 403);

        $data = $this->validated($request);
        $document->fill($data);
        $document->updated_by = $request->user()->id;
        $document->save();

        $this->indexer->index($document);

        return redirect()
            ->route('staff.assistant.knowledge.index')
            ->with('status', 'Knowledge document updated and re-indexed.');
    }

    public function destroy(Request $request, AssistantDocument $document): RedirectResponse
    {
        abort_unless($request->user()->can(AssistantPermissions::KNOWLEDGE_MANAGE), 403);

        $document->chunks()->delete();
        $document->delete();

        return redirect()
            ->route('staff.assistant.knowledge.index')
            ->with('status', 'Knowledge document removed.');
    }

    public function reindex(Request $request, AssistantDocument $document): RedirectResponse
    {
        abort_unless($request->user()->can(AssistantPermissions::KNOWLEDGE_MANAGE), 403);

        $chunks = $this->indexer->index($document);

        return back()->with('status', "Re-indexed \"{$document->title}\" into {$chunks} chunks.");
    }

    public function reindexAll(Request $request): RedirectResponse
    {
        abort_unless($request->user()->can(AssistantPermissions::KNOWLEDGE_MANAGE), 403);

        $chunks = $this->indexer->reindexAll(force: true);

        return back()->with('status', "Re-indexed the knowledge base into {$chunks} chunks.");
    }

    /**
     * @return array<string, mixed>
     */
    private function validated(Request $request): array
    {
        $validated = $request->validate([
            'title' => ['required', 'string', 'max:180'],
            'category' => ['required', 'string', Rule::in(array_keys(AssistantDocument::CATEGORIES))],
            'summary' => ['nullable', 'string', 'max:280'],
            'body' => ['required', 'string', 'max:20000'],
            'is_published' => ['nullable', 'boolean'],
        ]);

        $validated['is_published'] = $request->boolean('is_published');
        $validated['source_type'] = 'manual';

        return $validated;
    }

    private function uniqueSlug(string $title): string
    {
        $base = Str::slug($title) ?: 'document';
        $slug = $base;
        $suffix = 2;

        while (AssistantDocument::withTrashed()->where('slug', $slug)->exists()) {
            $slug = $base.'-'.$suffix++;
        }

        return $slug;
    }
}
