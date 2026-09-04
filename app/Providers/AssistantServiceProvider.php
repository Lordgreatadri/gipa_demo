<?php

namespace App\Providers;

use App\Services\Assistant\AssistantService;
use App\Services\Assistant\Contracts\ChatProvider;
use App\Services\Assistant\Contracts\EmbeddingProvider;
use App\Services\Assistant\Guardrails;
use App\Services\Assistant\KnowledgeRetriever;
use App\Services\Assistant\Providers\OfflineChatProvider;
use App\Services\Assistant\Providers\OfflineEmbeddingProvider;
use App\Services\Assistant\Providers\OpenAiChatProvider;
use App\Services\Assistant\Providers\OpenAiEmbeddingProvider;
use App\Services\Assistant\Tools\CertificateVerificationTool;
use App\Services\Assistant\Tools\DistrictOverviewTool;
use App\Services\Assistant\Tools\InvestorOnboardingGuideTool;
use App\Services\Assistant\Tools\PlatformStatsTool;
use App\Services\Assistant\Tools\SearchOpportunitiesTool;
use App\Services\Assistant\Tools\SectorCatalogTool;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;

class AssistantServiceProvider extends ServiceProvider
{
    /** @var array<int, class-string> */
    private const TOOLS = [
        SearchOpportunitiesTool::class,
        SectorCatalogTool::class,
        DistrictOverviewTool::class,
        InvestorOnboardingGuideTool::class,
        PlatformStatsTool::class,
        CertificateVerificationTool::class,
    ];

    public function register(): void
    {
        $this->app->bind(EmbeddingProvider::class, function ($app): EmbeddingProvider {
            return $this->driver() === 'openai'
                ? $app->make(OpenAiEmbeddingProvider::class)
                : $app->make(OfflineEmbeddingProvider::class);
        });

        $this->app->bind(ChatProvider::class, function ($app): ChatProvider {
            return $this->driver() === 'openai'
                ? $app->make(OpenAiChatProvider::class)
                : $app->make(OfflineChatProvider::class);
        });

        $this->app->tag(self::TOOLS, 'assistant.tools');

        $this->app->bind(AssistantService::class, function ($app): AssistantService {
            return new AssistantService(
                $app->make(ChatProvider::class),
                $app->make(KnowledgeRetriever::class),
                $app->make(Guardrails::class),
                iterator_to_array($app->tagged('assistant.tools')),
            );
        });
    }

    public function boot(): void
    {
        RateLimiter::for('assistant', fn (Request $request) => Limit::perMinute(
            (int) config('assistant.rate_limit.per_minute', 15)
        )->by($request->user()?->uuid ?? $request->ip()));
    }

    private function driver(): string
    {
        return (string) config('assistant.driver', 'offline');
    }
}
