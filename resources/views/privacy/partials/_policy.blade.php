@if ($privacy->privacy_policy_content)
    <div class="privacy-prose">
        {!! $privacy->privacy_policy_content !!}
    </div>
@else
    <x-empty-state
        icon="fa-solid fa-shield-halved"
        title="No privacy policy yet"
        description="No privacy policy has been configured yet. Please contact the administrator."
    />
@endif

@if ($privacy->privacy_policy_version)
    <p class="mt-4 border-t border-border pt-4 text-xs text-ink-muted">Version: {{ $privacy->privacy_policy_version }}</p>
@endif
