<?php

namespace App\Services\Assistant;

use App\Models\AssistantDocument;
use App\Services\Assistant\Contracts\EmbeddingProvider;
use App\Services\Assistant\Support\Text;
use Illuminate\Support\Facades\DB;

/**
 * Splits knowledge documents into overlapping chunks and stores an embedding
 * for each so they can be retrieved by semantic similarity.
 */
class KnowledgeIndexer
{
    public function __construct(private readonly EmbeddingProvider $embeddings) {}

    /**
     * (Re)index a single document. Returns the number of chunks written.
     */
    public function index(AssistantDocument $document): int
    {
        $chunks = $this->chunk($document->body);

        DB::transaction(function () use ($document, $chunks): void {
            $document->chunks()->delete();

            foreach ($chunks as $ordinal => $content) {
                $document->chunks()->create([
                    'ordinal' => $ordinal,
                    'content' => $content,
                    'token_estimate' => Text::estimateTokens($content),
                    'embedding' => $this->embeddings->embed($document->title.' '.$content),
                    'embedding_model' => $this->embeddings->model(),
                ]);
            }

            $document->forceFill([
                'checksum' => md5($document->body),
                'indexed_at' => now(),
            ])->save();
        });

        return count($chunks);
    }

    /**
     * Index every published document that is out of date. Returns chunk total.
     */
    public function reindexAll(bool $force = false): int
    {
        $total = 0;

        AssistantDocument::query()
            ->where('is_published', true)
            ->orderBy('id')
            ->each(function (AssistantDocument $document) use (&$total, $force): void {
                if ($force || $document->needsIndexing()) {
                    $total += $this->index($document);
                }
            });

        return $total;
    }

    /**
     * @return array<int, string>
     */
    public function chunk(string $body): array
    {
        $size = (int) config('assistant.retrieval.chunk_size', 900);
        $overlap = (int) config('assistant.retrieval.chunk_overlap', 150);

        $paragraphs = preg_split('/\n{2,}/', trim($body)) ?: [];
        $paragraphs = array_values(array_filter(array_map('trim', $paragraphs)));

        $chunks = [];
        $current = '';

        foreach ($paragraphs as $paragraph) {
            if ($current !== '' && mb_strlen($current) + mb_strlen($paragraph) + 2 > $size) {
                $chunks[] = $current;
                $current = $overlap > 0 ? mb_substr($current, -$overlap).' ' : '';
            }

            $current = trim($current.' '.$paragraph);

            // A single very long paragraph is split hard on size.
            while (mb_strlen($current) > $size) {
                $chunks[] = mb_substr($current, 0, $size);
                $current = ($overlap > 0 ? mb_substr($current, $size - $overlap) : mb_substr($current, $size));
            }
        }

        if (trim($current) !== '') {
            $chunks[] = trim($current);
        }

        return $chunks === [] ? [trim($body)] : $chunks;
    }
}
