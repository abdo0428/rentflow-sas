<x-app-layout>
    <x-slot name="breadcrumbs"><x-breadcrumbs :items="[__('app.maintenance') => route('maintenance.index'), __('workflow.create_request') => null]"/></x-slot>
    <x-page-header :title="__('workflow.create_request')" :description="__('workflow.maintenance_intro')" icon="maintenance"/>
    <form method="POST" enctype="multipart/form-data" action="{{ route('maintenance.store') }}" class="space-y-6" x-data="{ submitting: false }" @submit="submitting = true">
        @csrf
        <x-form-section :title="__('workflow.request_info')" :description="__('workflow.request_info_hint')" icon="maintenance">
            <div class="sm:col-span-2"><x-field name="title" label="workflow.attributes.title" maxlength="255" autofocus/></div>
            @unless(auth()->user()->hasRole('tenant'))<x-select name="tenant_id" :label="__('workflow.attributes.tenant_id')" :options="$tenants" required/>@endunless
            <x-select name="unit_id" :label="__('workflow.attributes.unit_id')" :options="$units" required/>
            <x-select name="priority" :label="__('app.priority')" :options="collect(['low','medium','high','urgent'])->mapWithKeys(fn ($s) => [$s => __('app.'.$s)])" value="medium" required/>
            <x-field name="preferred_date" label="workflow.attributes.preferred_date" type="date" :min="today()->format('Y-m-d')" :required="false"/>
            <div class="sm:col-span-2"><x-textarea name="description" :label="__('workflow.attributes.description')" maxlength="10000" required/></div>
            <div class="sm:col-span-2"><label for="photo" class="label">{{ __('portal.photo') }}</label><input id="photo" name="photo" type="file" accept="image/jpeg,image/png,image/webp" class="field" aria-describedby="photo-hint photo-error"><p id="photo-hint" class="mt-2 text-xs text-slate-400">{{ __('portal.photo_hint') }}</p><x-input-error id="photo-error" :messages="$errors->get('photo')" class="mt-2"/></div>
        </x-form-section>
        <x-form-footer :cancel="route('maintenance.index')" :label="__('workflow.create_request')"/>
    </form>
</x-app-layout>
