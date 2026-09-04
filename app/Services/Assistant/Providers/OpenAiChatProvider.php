<?php

namespace App\Services\Assistant\Providers;

use App\Services\Assistant\Contracts\ChatProvider;
use App\Services\Assistant\Data\AssistantAnswer;
use App\Services\Assistant\Data\AssistantPrompt;
use Illuminate\Support\Facades\Http;
use RuntimeException;

/**
 * OpenAI chat driver. Inactive unless ASSISTANT_DRIVER=openai and OPENAI_API_KEY
 * are configured. It grounds the model on the same tool results and knowledge
 * chunks used by the offline driver, and enforces the institutional guardrail
 * system prompt. Requires no extra composer package (uses the HTTP client).
 */
class OpenAiChatProvider implements ChatProvider
{
    public function generate(AssistantPrompt $prompt): AssistantAnswer
    {
        $config = config('assistant.providers.openai');

        if (empty($config['api_key'])) {
            throw new RuntimeException('OpenAI API key is not configured.');
        }

        if (! $prompt->hasContext()) {
            $content = $prompt->flagged
                ? (string) config('assistant.guardrails.injection_deflection')
                : (string) config('assistant.guardrails.refusal_message');

            return new AssistantAnswer(
                content: $content,
                grounded: false,
                provider: $this->name(),
                model: $this->model(),
            );
        }

        $messages = $this->buildMessages($prompt);

        $response = Http::withToken($config['api_key'])
            ->baseUrl($config['base_url'])
            ->timeout($config['timeout'] ?? 30)
            ->post('/chat/completions', [
                'model' => $config['chat_model'],
                'temperature' => 0.2,
                'messages' => $messages,
            ])
            ->throw()
            ->json();

        $content = trim((string) ($response['choices'][0]['message']['content'] ?? ''));
        $usage = $response['usage'] ?? [];

        return new AssistantAnswer(
            content: $content,
            citations: $this->buildCitations($prompt),
            toolsUsed: array_values(array_unique(array_map(
                static fn ($result) => $result->tool,
                $prompt->toolResults,
            ))),
            grounded: true,
            provider: $this->name(),
            model: $this->model(),
            tokensInput: (int) ($usage['prompt_tokens'] ?? 0),
            tokensOutput: (int) ($usage['completion_tokens'] ?? 0),
        );
    }

    public function name(): string
    {
        return 'openai';
    }

    public function model(): string
    {
        return (string) config('assistant.providers.openai.chat_model', 'gpt-4o-mini');
    }

    /**
     * @return array<int, array{role: string, content: string}>
     */
    private function buildMessages(AssistantPrompt $prompt): array
    {
        $context = '';
        foreach ($prompt->toolResults as $result) {
            $context .= "[Live data — {$result->sourceLabel}]\n{$result->summary}\n\n";
        }
        foreach ($prompt->chunks as $chunk) {
            $context .= "[Knowledge — {$chunk->documentTitle}]\n{$chunk->content}\n\n";
        }

        $messages = [[
            'role' => 'system',
            'content' => config('assistant.guardrails.system_prompt')
                ."\n\nUse only the following verified context. If it does not answer the question, say so.\n\n"
                .trim($context),
        ]];

        foreach ($prompt->history as $turn) {
            $messages[] = ['role' => $turn['role'], 'content' => $turn['content']];
        }

        $messages[] = ['role' => 'user', 'content' => $prompt->question];

        return $messages;
    }

    /**
     * @return array<int, array{type: string, label: string, category: string|null, reference: string|null}>
     */
    private function buildCitations(AssistantPrompt $prompt): array
    {
        $citations = [];

        foreach ($prompt->toolResults as $result) {
            $citations[] = [
                'type' => 'tool',
                'label' => $result->sourceLabel,
                'category' => null,
                'reference' => $result->reference,
            ];
        }

        foreach ($prompt->chunks as $chunk) {
            $citations[] = [
                'type' => 'knowledge',
                'label' => $chunk->documentTitle,
                'category' => $chunk->category,
                'reference' => $chunk->documentSlug,
            ];
        }

        return $citations;
    }
}
