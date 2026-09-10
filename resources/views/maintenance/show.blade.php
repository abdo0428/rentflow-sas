<x-app-layout>
    <x-slot name="breadcrumbs"><x-breadcrumbs :items="[__('app.maintenance') => route('maintenance.index'), $maintenanceRequest->title => null]"/></x-slot>
    <x-page-header :title="$maintenanceRequest->title" :description="$maintenanceRequest->building_label.' · '.$maintenanceRequest->unit_label" icon="maintenance"><x-slot name="actions"><x-badge :value="$maintenanceRequest->priority"/><x-badge :value="$maintenanceRequest->status"/></x-slot></x-page-header>
    <div class="grid items-start gap-6 xl:grid-cols-[3fr_2fr]">
        <div class="min-w-0 space-y-6">
            @if($maintenanceRequest->photo_path)<a href="{{ route('maintenance.photo', $maintenanceRequest) }}" class="btn-secondary"><x-icon name="documents"/>{{ __('portal.view_photo') }}</a>@endif
            <x-card :title="__('workflow.request_description')"><p class="whitespace-pre-line break-words p-6 text-sm leading-7 text-slate-600">{{ $maintenanceRequest->description }}</p><x-details-list :record="$maintenanceRequest" :fields="['priority','preferred_date','created_at','completed_at']" translation="workflow.attributes"/><div class="border-t border-slate-100 px-6 py-4 text-sm"><span class="text-slate-400">{{ __('workflow.attributes.assigned_to') }}</span><span class="ms-3 font-semibold">{{ $maintenanceRequest->assignee?->name ?? __('workflow.unassigned') }}</span></div></x-card>
            <x-card :title="__('workflow.timeline')" :description="__('workflow.timeline_hint')">
                <ol class="m-6 space-y-6 border-s-2 border-emerald-100 ps-6">
                    @forelse($timeline as $event)<li class="relative"><span class="absolute -start-[33px] top-1 h-3.5 w-3.5 rounded-full border-4 border-white bg-emerald-600 ring-1 ring-emerald-200"></span><div class="flex flex-wrap items-center gap-2">@if($event->from_status)<x-badge :value="$event->from_status"/><span class="inline-block text-slate-300 rtl:rotate-180" aria-hidden="true">→</span>@endif<x-badge :value="$event->to_status"/></div><p class="mt-2 text-xs text-slate-400">{{ $event->user?->name ?? __('workflow.system') }} · <time datetime="{{ $event->created_at->toIso8601String() }}">{{ $event->created_at->format('Y-m-d H:i') }}</time></p></li>
                    @empty<li><p class="text-sm font-medium">{{ __('workflow.created') }}</p><p class="mt-2 text-xs text-slate-400">{{ $maintenanceRequest->created_at->format('Y-m-d H:i') }}</p></li>@endforelse
                </ol><div class="px-6 pb-4">{{ $timeline->links('components.pagination') }}</div>
            </x-card>
            @if($notes !== null)
                <x-card :title="__('workflow.internal_notes')" :description="__('workflow.internal_notes_hint')">
                    <div class="divide-y divide-slate-100">@forelse($notes as $note)<div class="p-6"><p class="whitespace-pre-line break-words text-sm leading-7">{{ $note->body }}</p><p class="mt-3 text-xs text-slate-400">{{ $note->user?->name ?? __('workflow.system') }} · {{ $note->created_at->format('Y-m-d H:i') }}</p></div>@empty<p class="muted p-6">{{ __('workflow.no_notes') }}</p>@endforelse</div>
                    <div class="px-6">{{ $notes->links('components.pagination') }}</div>
                    <form method="POST" action="{{ route('maintenance.note', $maintenanceRequest) }}" class="space-y-4 border-t border-slate-100 p-6" x-data="{ submitting: false }" @submit="submitting = true">@csrf<x-textarea name="body" :label="__('workflow.internal_note')" maxlength="5000" required/><x-submit>{{ __('workflow.add_note') }}</x-submit></form>
                </x-card>
            @endif
        </div>
        <div class="min-w-0 space-y-6">
            @can('assign', $maintenanceRequest)
                @if($maintenanceRequest->status === 'under_review')
                <x-card :title="__('workflow.assign_staff')"><form method="POST" action="{{ route('maintenance.assign', $maintenanceRequest) }}" class="space-y-5 p-6" x-data="{ submitting: false }" @submit="submitting = true">@csrf @method('PATCH')<x-select name="assigned_to" :label="__('workflow.attributes.assigned_to')" :options="$staff" required/><x-submit>{{ __('workflow.assign_staff') }}</x-submit></form></x-card>
                @endif
            @endcan
            @if($transitions)
                <x-card :title="__('workflow.change_status')"><form method="POST" action="{{ route('maintenance.transition', $maintenanceRequest) }}" class="space-y-5 p-6" x-data="{ submitting: false }" @submit="submitting = true">@csrf @method('PATCH')<x-select name="status" :label="__('app.status')" :options="$transitions" required/><x-submit>{{ __('workflow.update_status') }}</x-submit></form></x-card>
            @elseif(in_array($maintenanceRequest->status, ['completed','rejected','cancelled']))
                <div class="card p-6"><x-badge :value="$maintenanceRequest->status"/><p class="muted mt-4">{{ __('workflow.terminal_status') }}</p></div>
            @endif
            @role('maintenance_staff')<div class="rounded-xl border border-emerald-100 bg-emerald-50 p-5 text-sm leading-7 text-emerald-800">{{ __('workflow.staff_note') }}</div>@endrole
        </div>
    </div>
</x-app-layout>
