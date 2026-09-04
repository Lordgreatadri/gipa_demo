<?php

namespace App\Http\Controllers;

use App\Http\Requests\AssistantChatRequest;
use App\Models\AssistantConversation;
use App\Services\Assistant\AssistantService;
use Illuminate\Http\JsonResponse;

class AssistantController extends Controller
{
    public function __construct(private readonly AssistantService $assistant) {}

    public function store(AssistantChatRequest $request): JsonResponse
    {
        $user = $request->user();
        $conversation = $this->resolveConversation($request->input('conversation'), $request);

        ['conversation' => $conversation, 'message' => $message] = $this->assistant->ask(
            message: (string) $request->input('message'),
            conversation: $conversation,
            user: $user,
            meta: [
                'channel' => $user ? 'portal' : 'public',
                'session_token' => $this->sessionToken($request),
                'ip_hash' => hash('sha256', (string) $request->ip()),
            ],
        );

        return response()->json([
            'conversation' => $conversation->uuid,
            'reply' => [
                'content' => $message->content,
                'citations' => $message->citations ?? [],
                'tools' => $message->tools_used ?? [],
                'grounded' => $message->was_grounded,
                'created_at' => $message->created_at?->toIso8601String(),
            ],
        ]);
    }

    /**
     * Resolve a resumed conversation, enforcing ownership so conversations
     * cannot be hijacked by guessing a UUID.
     */
    private function resolveConversation(?string $uuid, AssistantChatRequest $request): ?AssistantConversation
    {
        if ($uuid === null || $uuid === '') {
            return null;
        }

        $conversation = AssistantConversation::query()->where('uuid', $uuid)->first();

        if ($conversation === null) {
            return null;
        }

        // Authenticated conversations must belong to the current user so they
        // cannot be resumed by a guest or a different signed-in user.
        if ($conversation->user_id !== null) {
            return $conversation->user_id === $request->user()?->id ? $conversation : null;
        }

        // Guest conversations must originate from the same browser session. The
        // persisted session token is required in addition to the UUID so a
        // leaked conversation id alone cannot be used to append to, or read the
        // prior turns of, another visitor's conversation.
        return $request->user() === null
            && hash_equals((string) $conversation->session_token, $this->sessionToken($request))
                ? $conversation
                : null;
    }

    private function sessionToken(AssistantChatRequest $request): string
    {
        return hash('sha256', $request->session()->getId());
    }
}
