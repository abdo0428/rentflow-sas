@props(['name', 'type' => 'text', 'label' => null, 'value' => '', 'required' => true])
<div>
    <label for="{{ $name }}" class="label">{{ __($label ?? 'app.'.$name) }}</label>
    <input id="{{ $name }}" name="{{ $name }}" type="{{ $type }}" value="{{ $type === 'password' ? '' : old($name, $value) }}" @required($required) aria-invalid="{{ $errors->has($name) ? 'true' : 'false' }}" aria-describedby="{{ $name }}-error" {{ $attributes->class(['field']) }}>
    <x-input-error :messages="$errors->get($name)" id="{{ $name }}-error" class="mt-2"/>
</div>