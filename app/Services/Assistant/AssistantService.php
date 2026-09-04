<?php

namespace App\Services\Assistant;

use App\Models\AssistantConversation;
use App\Models\AssistantMessage;
use App\Models\User;
use App\Services\Assistant\Contracts\AssistantTool;
use App\Services\Assistant\Contracts\ChatProvider;
use App\Services\Assistant\Data\AssistantAnswer;
use App\Services\Assistant\Data\AssistantPrompt;
use App\Services\Assistant\Data\ToolResult;
use App\Services\Assistant\Support\Text;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Throwable;

/**
 * Orchestrates a single assistant turn: guardrails → tools → retrieval →
 * generation → persistence, with usage metrics and governance logging.
 */
class AssistantService
{
    /** @var array<int, AssistantTool> */
    private array $tools;

    /**
     * @param  iterable<AssistantTool>  $tools
     */
    public function __construct(
        private readonly ChatProvider $chat,
        private readonly KnowledgeRetriever $retriever,
        private readonly Guardrails $guardrails,
        iterable $tools = [],
    ) {
        $this->tools = is_array($tools) ? $tools : iterator_to_array($tools);
    }

    /**
     * @param  array{channel?: string, session_token?: string|null, ip_hash?: string|null}  $meta
     * @return array{conversation: AssistantConversation, message: AssistantMessage}
     */
    public function ask(string $message, ?AssistantConversation $conversation, ?User $user, array $meta = []): array
    {
        $clean = $this->guardrails->sanitize($message);
        $flagged = $this->guardrails->detectInjection($clean);

        $conversation = $this->resolveConversation($conversation, $user, $meta);

        // Capture prior turns before persisting the current question so it is
        // not sent twice when a provider appends the question to the history.
        $history = $this->history($conversation);

        $conversation->messages()->create([
            'role' => AssistantMessage::ROLE_USER,
            'content' => $clean,
            'flagged' => $flagged,
        ]);

        $toolResults = $this->runTools($clean);
        $chunks = $this->retriever->retrieve($clean);

        $prompt = new AssistantPrompt(
            question: $clean,
            history: $history,
            toolResults: $toolResults,
            chunks: $chunks,
            flagged: $flagged,
        );

        $startedAt = microtime(true);
        $answer = $this->generate($prompt);
        $latencyMs = (int) round((microtime(true) - $startedAt) * 1000);

        $assistantMessage = $conversation->messages()->create([
            'role' => AssistantMessage::ROLE_ASSISTANT,
            'content' => $answer->content,
            'citations' => $answer->citations,
            'tools_used' => $answer->toolsUsed,
            'provider' => $answer->provider,
            'model' => $answer->model,
            'tokens_input' => $answer->tokensInput,
            'tokens_output' => $answer->tokensOutput,
            'latency_ms' => $latencyMs,
            'was_grounded' => $answer->grounded,
            'flagged' => $flagged,
        ]);

        $conversation->forceFill([
            'last_activity_at' => now(),
            'title' => $conversation->title ?: Str::limit($clean, 60),
        ])->save();

        Log::channel(config('logging.default'))->info('assistant.turn', [
            'conversation' => $conversation->uuid,
            'provider' => $answer->provider,
            'grounded' => $answer->grounded,
            'flagged' => $flagged,
            'tools' => $answer->toolsUsed,
            'sources' => count($answer->citations),
            'latency_ms' => $latencyMs,
        ]);

        return ['conversation' => $conversation, 'message' => $assistantMessage];
    }

    /**
     * @return array<int, ToolResult>
     */
    private function runTools(string $question): array
    {
        $results = [];

        foreach ($this->tools as $tool) {
            if (! $tool->matches($question)) {
                continue;
            }

            try {
                $result = $tool->handle($question);
            } catch (Throwable $e) {
                Log::warning('assistant.tool_failed', ['tool' => $tool->name(), 'error' => $e->getMessage()]);

                continue;
            }

            if ($result instanceof ToolResult) {
                $results[] = $result;
            }
        }

        return $results;
    }

    private function generate(AssistantPrompt $prompt): AssistantAnswer
    {
        try {
            return $this->chat->generate($prompt);
        } catch (Throwable $e) {
            Log::error('assistant.generation_failed', ['error' => $e->getMessage()]);

            return new AssistantAnswer(
                content: (string) config('assistant.guardrails.refusal_message'),
                grounded: false,
                provider: $this->chat->name(),
                model: $this->chat->model(),
                tokensInput: Text::estimateTokens($prompt->question),
            );
        }
    }

    /**
     * @param  array{channel?: string, session_token?: string|null, ip_hash?: string|null}  $meta
     */
    private function resolveConversation(?AssistantConversation $conversation, ?User $user, array $meta): AssistantConversation
    {
        if ($conversation !== null) {
            return $conversation;
        }

        return AssistantConversation::create([
            'user_id' => $user?->id,
            'session_token' => $meta['session_token'] ?? null,
            'channel' => $meta['channel'] ?? 'public',
            'ip_hash' => $meta['ip_hash'] ?? null,
            'last_activity_at' => now(),
        ]);
    }

    /**
     * @return array<int, array{role: string, content: string}>
     */
    private function history(AssistantConversation $conversation): array
    {
        $limit = (int) config('assistant.conversation.max_history_messages', 10);

        return $conversation->messages()
            ->orderByDesc('id')
            ->limit($limit)
            ->get(['role', 'content'])
            ->reverse()
            ->map(fn (AssistantMessage $message): array => [
                'role' => $message->role,
                'content' => $message->content,
            ])
            ->values()
            ->all();
    }
}
