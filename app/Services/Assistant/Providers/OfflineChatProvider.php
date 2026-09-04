<?php

namespace App\Services\Assistant\Providers;

use App\Services\Assistant\Contracts\ChatProvider;
use App\Services\Assistant\Data\AssistantAnswer;
use App\Services\Assistant\Data\AssistantPrompt;
use App\Services\Assistant\Support\Text;

/**
 * Deterministic retrieval-augmented responder.
 *
 * This driver never invents content: it composes answers strictly from the
 * tool results and knowledge-base chunks supplied in the prompt, attaches
 * citations, and falls back to a governed refusal when no verified context is
 * available. It requires no external API and powers tests, demos and offline
 * deployments. Swap in the OpenAI driver via config to add generative phrasing.
 */
class OfflineChatProvider implements ChatProvider
{
    private const MAX_CHUNK_EXCERPT = 600;

    public function generate(AssistantPrompt $prompt): AssistantAnswer
    {
        $tokensInput = Text::estimateTokens($prompt->question.$this->contextText($prompt));

        // A message that is only a manipulation attempt with no real question.
        if ($prompt->flagged && ! $prompt->hasContext()) {
            $content = (string) config('assistant.guardrails.injection_deflection');

            return new AssistantAnswer(
                content: $content,
                grounded: false,
                provider: $this->name(),
                model: $this->model(),
                tokensInput: $tokensInput,
                tokensOutput: Text::estimateTokens($content),
            );
        }

        if (! $prompt->hasContext()) {
            $content = (string) config('assistant.guardrails.refusal_message');

            return new AssistantAnswer(
                content: $content,
                grounded: false,
                provider: $this->name(),
                model: $this->model(),
                tokensInput: $tokensInput,
                tokensOutput: Text::estimateTokens($content),
            );
        }

        $segments = [];
        $citations = [];
        $toolsUsed = [];

        foreach ($prompt->toolResults as $result) {
            $segments[] = $result->summary;
            $toolsUsed[] = $result->tool;
            $citations[] = [
                'type' => 'tool',
                'label' => $result->sourceLabel,
                'category' => null,
                'reference' => $result->reference,
            ];
        }

        $chunkExcerpts = [];
        foreach ($prompt->chunks as $chunk) {
            $chunkExcerpts[] = $this->excerpt($chunk->content);
            $citations[] = [
                'type' => 'knowledge',
                'label' => $chunk->documentTitle,
                'category' => $chunk->category,
                'reference' => $chunk->documentSlug,
            ];
        }

        if ($segments === []) {
            // Knowledge-only answer.
            $body = implode("\n\n", array_slice($chunkExcerpts, 0, 2));
        } else {
            $body = implode("\n\n", $segments);
            if ($chunkExcerpts !== []) {
                $body .= "\n\n".$chunkExcerpts[0];
            }
        }

        return new AssistantAnswer(
            content: $body,
            citations: $this->dedupeCitations($citations),
            toolsUsed: array_values(array_unique($toolsUsed)),
            grounded: true,
            provider: $this->name(),
            model: $this->model(),
            tokensInput: $tokensInput,
            tokensOutput: Text::estimateTokens($body),
        );
    }

    public function name(): string
    {
        return 'offline';
    }

    public function model(): string
    {
        return 'offline-rag';
    }

    private function contextText(AssistantPrompt $prompt): string
    {
        $text = '';
        foreach ($prompt->toolResults as $result) {
            $text .= ' '.$result->summary;
        }
        foreach ($prompt->chunks as $chunk) {
            $text .= ' '.$chunk->content;
        }

        return $text;
    }

    private function excerpt(string $content): string
    {
        $content = Text::clean($content);

        if (mb_strlen($content) <= self::MAX_CHUNK_EXCERPT) {
            return $content;
        }

        $trimmed = mb_substr($content, 0, self::MAX_CHUNK_EXCERPT);
        $lastStop = mb_strrpos($trimmed, '. ');

        if ($lastStop !== false && $lastStop > self::MAX_CHUNK_EXCERPT * 0.5) {
            return mb_substr($trimmed, 0, $lastStop + 1);
        }

        return rtrim($trimmed).'…';
    }

    /**
     * @param  array<int, array{type: string, label: string, category: string|null, reference: string|null}>  $citations
     * @return array<int, array{type: string, label: string, category: string|null, reference: string|null}>
     */
    private function dedupeCitations(array $citations): array
    {
        $seen = [];
        $unique = [];

        foreach ($citations as $citation) {
            $key = $citation['type'].'|'.$citation['label'];
            if (isset($seen[$key])) {
                continue;
            }
            $seen[$key] = true;
            $unique[] = $citation;
        }

        return $unique;
    }
}
