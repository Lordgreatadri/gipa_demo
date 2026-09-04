@php
    $assistantName = config('assistant.branding.name');
    $assistantTagline = config('assistant.branding.tagline');
@endphp
<div class="assistant-widget" data-assistant data-endpoint="{{ route('assistant.chat') }}">
    <button type="button" class="assistant-launcher" data-assistant-toggle aria-expanded="false" aria-controls="assistant-panel" aria-label="Open the {{ $assistantName }}">
        <svg class="assistant-launcher__open" viewBox="0 0 24 24" aria-hidden="true"><path d="M21 15a2 2 0 0 1-2 2H8l-5 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"></path></svg>
        <svg class="assistant-launcher__close" viewBox="0 0 24 24" aria-hidden="true"><path d="M6 6l12 12M18 6 6 18"></path></svg>
    </button>

    <section class="assistant-panel" id="assistant-panel" data-assistant-panel hidden aria-label="{{ $assistantName }}">
        <header class="assistant-panel__header">
            <div class="assistant-panel__identity">
                <span class="assistant-panel__avatar" aria-hidden="true">
                    <svg viewBox="0 0 24 24"><rect x="4" y="7" width="16" height="12" rx="3"></rect><path d="M12 3v4M9 13h.01M15 13h.01"></path></svg>
                </span>
                <span class="assistant-panel__titles">
                    <strong>{{ $assistantName }}</strong>
                    <small>{{ $assistantTagline }}</small>
                </span>
            </div>
            <button type="button" class="assistant-panel__close" data-assistant-toggle aria-label="Close assistant">
                <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M6 6l12 12M18 6 6 18"></path></svg>
            </button>
        </header>

        <div class="assistant-thread" data-assistant-thread role="log" aria-live="polite">
            <div class="assistant-message assistant-message--bot">
                <p>Hello! I'm the {{ $assistantName }}. I can help with published investment opportunities, sectors, districts, investor onboarding and certificate verification. What would you like to know?</p>
            </div>
        </div>

        <div class="assistant-suggestions" data-assistant-suggestions>
            <button type="button" data-assistant-suggestion>What sectors can I invest in?</button>
            <button type="button" data-assistant-suggestion>How do I become an investor?</button>
            <button type="button" data-assistant-suggestion>How do I verify a certificate?</button>
        </div>

        <form class="assistant-form" data-assistant-form>
            <label class="assistant-form__field">
                <span class="sr-only">Ask the assistant</span>
                <input type="text" name="message" data-assistant-input autocomplete="off" placeholder="Ask about opportunities, sectors, districts…" maxlength="{{ (int) config('assistant.guardrails.max_question_length', 1000) }}" required>
            </label>
            <button type="submit" class="assistant-form__send" data-assistant-send aria-label="Send">
                <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M22 2 11 13M22 2l-7 20-4-9-9-4z"></path></svg>
            </button>
        </form>
        <p class="assistant-disclaimer">Answers are drawn from verified GIPA platform data. For personalised advice, contact GIPA directly.</p>
    </section>
</div>
