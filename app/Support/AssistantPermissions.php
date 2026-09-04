<?php

namespace App\Support;

final class AssistantPermissions
{
    public const KNOWLEDGE_VIEW = 'assistant.knowledge.view';

    public const KNOWLEDGE_MANAGE = 'assistant.knowledge.manage';

    public const CONVERSATIONS_VIEW = 'assistant.conversations.view';

    public const ALL = [
        self::KNOWLEDGE_VIEW,
        self::KNOWLEDGE_MANAGE,
        self::CONVERSATIONS_VIEW,
    ];
}
