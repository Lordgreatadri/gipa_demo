<?php

namespace App\Services\Assistant\Tools;

use App\Models\InvestorDocumentType;
use App\Services\Assistant\Data\ToolResult;

/**
 * Explains how to become an investor and which KYC documents are required,
 * sourced from the live investor document-type reference data.
 */
class InvestorOnboardingGuideTool extends AbstractTool
{
    public function name(): string
    {
        return 'investor_onboarding_guide';
    }

    protected function triggers(): array
    {
        return ['onboard', 'register', 'sign up', 'signup', 'kyc', 'become an investor', 'how do i invest', 'how to invest', 'get started', 'documents required', 'documents needed', 'account'];
    }

    public function handle(string $question): ?ToolResult
    {
        $types = InvestorDocumentType::query()
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get(['name', 'is_required']);

        $steps = "Getting started as an investor on the platform:\n"
            ."• Create an investor account and verify your email.\n"
            ."• Complete your investor profile and investment mandate.\n"
            ."• Submit your KYC documents for review.\n"
            .'• Once approved, you can express interest in opportunities and receive matches.';

        if ($types->isNotEmpty()) {
            $required = $types->where('is_required', true)->pluck('name');
            $optional = $types->where('is_required', false)->pluck('name');

            if ($required->isNotEmpty()) {
                $steps .= "\n\nRequired KYC documents:\n".$required->map(fn ($n) => '• '.$n)->implode("\n");
            }

            if ($optional->isNotEmpty()) {
                $steps .= "\n\nSupporting documents (where applicable):\n".$optional->map(fn ($n) => '• '.$n)->implode("\n");
            }
        }

        return new ToolResult(
            tool: $this->name(),
            summary: $steps,
            sourceLabel: 'Investor onboarding requirements',
            reference: route('register'),
            data: ['document_types' => $types->count()],
        );
    }
}
