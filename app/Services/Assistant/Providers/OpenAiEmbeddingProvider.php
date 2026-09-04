<?php

namespace App\Services\Assistant\Providers;

use App\Services\Assistant\Contracts\EmbeddingProvider;
use Illuminate\Support\Facades\Http;
use RuntimeException;

/**
 * OpenAI embedding driver. Inactive unless ASSISTANT_DRIVER=openai and
 * OPENAI_API_KEY are configured. Uses the framework HTTP client so no extra
 * package is required; enable it purely through configuration.
 */
class OpenAiEmbeddingProvider implements EmbeddingProvider
{
    public function embed(string $text): array
    {
        $config = config('assistant.providers.openai');

        if (empty($config['api_key'])) {
            throw new RuntimeException('OpenAI API key is not configured.');
        }

        $response = Http::withToken($config['api_key'])
            ->baseUrl($config['base_url'])
            ->timeout($config['timeout'] ?? 30)
            ->post('/embeddings', [
                'model' => $config['embedding_model'],
                'input' => $text,
            ])
            ->throw()
            ->json();

        return array_map('floatval', $response['data'][0]['embedding'] ?? []);
    }

    public function model(): string
    {
        return (string) config('assistant.providers.openai.embedding_model', 'text-embedding-3-small');
    }
}
