@props([
    'name' => 'password',
    'label' => 'Password',
    'autocomplete' => 'current-password',
])

@php($fieldId = 'password-' . str($name)->slug())

<div class="field">
    <label for="{{ $fieldId }}">{{ $label }}</label>
    <span class="password-control">
        <input id="{{ $fieldId }}" type="password" name="{{ $name }}" required autocomplete="{{ $autocomplete }}">
        <button type="button" data-password-toggle aria-controls="{{ $fieldId }}" aria-label="Show {{ str($label)->lower() }}" aria-pressed="false">
            <span data-password-show><i data-lucide="eye" aria-hidden="true"></i></span>
            <span data-password-hide hidden><i data-lucide="eye-off" aria-hidden="true"></i></span>
        </button>
    </span>
    @error($name)<small class="field-error">{{ $message }}</small>@enderror
</div>