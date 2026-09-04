<?php

namespace App\Services\Assistant;

use App\Models\AssistantDocumentChunk;
use App\Services\Assistant\Contracts\EmbeddingProvider;
use App\Services\Assistant\Data\RetrievedChunk;

/**
 * Retrieves the most relevant published knowledge-base chunks for a query using
 * cosine similarity over stored embeddings (computed in PHP for portability
 * across SQLite/MySQL — appropriate at platform-content scale).
 */
class KnowledgeRetriever
{
    public function __construct(private readonly EmbeddingProvider $embeddings) {}

    /**
     * @return array<int, RetrievedChunk>
     */
    public function retrieve(string $query): array
    {
        $maxChunks = (int) config('assistant.retrieval.max_chunks', 5);
        $minScore = (float) config('assistant.retrieval.min_score', 0.08);

        $queryVector = $this->embeddings->embed($query);

        if ($this->isZeroVector($queryVector)) {
            return [];
        }

        $candidates = AssistantDocumentChunk::query()
            ->join('assistant_documents', 'assistant_documents.id', '=', 'assistant_document_chunks.assistant_document_id')
            ->where('assistant_documents.is_published', true)
            ->whereNull('assistant_documents.deleted_at')
            ->get([
                'assistant_document_chunks.content',
                'assistant_document_chunks.embedding',
                'assistant_documents.title as document_title',
                'assistant_documents.slug as document_slug',
                'assistant_documents.category as document_category',
            ]);

        $scored = [];

        foreach ($candidates as $chunk) {
            $embedding = $chunk->embedding;

            if (! is_array($embedding) || $embedding === []) {
                continue;
            }

            $score = $this->cosine($queryVector, $embedding);

            if ($score < $minScore) {
                continue;
            }

            $scored[] = new RetrievedChunk(
                documentTitle: (string) $chunk->document_title,
                documentSlug: (string) $chunk->document_slug,
                category: (string) $chunk->document_category,
                content: (string) $chunk->content,
                score: $score,
            );
        }

        usort($scored, static fn (RetrievedChunk $a, RetrievedChunk $b): int => $b->score <=> $a->score);

        return array_slice($scored, 0, $maxChunks);
    }

    /**
     * @param  array<int, float>  $a
     * @param  array<int, float>  $b
     */
    private function cosine(array $a, array $b): float
    {
        $dot = 0.0;
        $magA = 0.0;
        $magB = 0.0;

        $length = min(count($a), count($b));

        for ($i = 0; $i < $length; $i++) {
            $dot += $a[$i] * $b[$i];
            $magA += $a[$i] * $a[$i];
            $magB += $b[$i] * $b[$i];
        }

        if ($magA <= 0.0 || $magB <= 0.0) {
            return 0.0;
        }

        return $dot / (sqrt($magA) * sqrt($magB));
    }

    /**
     * @param  array<int, float>  $vector
     */
    private function isZeroVector(array $vector): bool
    {
        foreach ($vector as $value) {
            if ($value !== 0.0) {
                return false;
            }
        }

        return true;
    }
}
