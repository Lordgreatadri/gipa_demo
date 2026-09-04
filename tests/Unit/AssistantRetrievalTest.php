<?php

namespace Tests\Unit;

use App\Models\AssistantDocument;
use App\Services\Assistant\KnowledgeIndexer;
use App\Services\Assistant\KnowledgeRetriever;
use App\Services\Assistant\Providers\OfflineEmbeddingProvider;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AssistantRetrievalTest extends TestCase
{
    use RefreshDatabase;

    private function indexer(): KnowledgeIndexer
    {
        return new KnowledgeIndexer(new OfflineEmbeddingProvider(128));
    }

    private function retriever(): KnowledgeRetriever
    {
        return new KnowledgeRetriever(new OfflineEmbeddingProvider(128));
    }

    public function test_indexer_chunks_and_embeds_a_document(): void
    {
        $document = AssistantDocument::create([
            'title' => 'Certificate verification',
            'slug' => 'certificate-verification',
            'category' => 'certificates',
            'body' => 'Scan the QR code to verify a certificate.'."\n\n".'The verification link shows the live status.',
        ]);

        $count = $this->indexer()->index($document);

        $this->assertGreaterThanOrEqual(1, $count);
        $this->assertNotNull($document->fresh()->indexed_at);
        $chunk = $document->chunks()->first();
        $this->assertIsArray($chunk->embedding);
        $this->assertNotEmpty($chunk->embedding);
    }

    public function test_retriever_ranks_relevant_document_above_irrelevant_one(): void
    {
        $indexer = $this->indexer();

        $onboarding = AssistantDocument::create([
            'title' => 'Investor onboarding and KYC',
            'slug' => 'onboarding',
            'category' => 'onboarding',
            'body' => 'To become an investor you register an account and submit your KYC documents for review.',
        ]);
        $certificates = AssistantDocument::create([
            'title' => 'Certificate verification',
            'slug' => 'certificates',
            'category' => 'certificates',
            'body' => 'Scan the QR code on a certificate to confirm it is genuine and see its status.',
        ]);

        $indexer->index($onboarding);
        $indexer->index($certificates);

        $results = $this->retriever()->retrieve('how do I register as an investor and submit kyc documents');

        $this->assertNotEmpty($results);
        $this->assertSame('Investor onboarding and KYC', $results[0]->documentTitle);
    }

    public function test_retriever_ignores_unpublished_documents(): void
    {
        $document = AssistantDocument::create([
            'title' => 'Draft policy',
            'slug' => 'draft-policy',
            'category' => 'policy',
            'is_published' => false,
            'body' => 'Draft investor incentive policy details for kyc onboarding registration.',
        ]);

        $this->indexer()->index($document);

        $results = $this->retriever()->retrieve('investor incentive policy onboarding registration');

        $this->assertEmpty($results);
    }
}
