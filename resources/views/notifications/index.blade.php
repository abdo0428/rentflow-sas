<x-app-layout>
    <x-slot name="header">{{ __('workflow.notifications') }}</x-slot>
    <x-page-header :title="__('workflow.notifications')" :description="__('workflow.notifications_intro')" icon="bell"/>
    <x-card>
        @if($notifications->isEmpty())<x-empty-state :title="__('workflow.no_notifications')" :description="__('workflow.no_notifications_hint')" icon="bell"/>
        @else<ul class="divide-y divide-slate-100">
            @foreach($notifications as $notification)<li @class(['flex flex-wrap items-start justify-between gap-4 p-6', 'bg-emerald-50/40' => ! $notification->read_at])><div class="flex min-w-0 flex-1 gap-4"><span class="rounded-xl bg-emerald-50 p-3 text-emerald-700"><x-icon name="bell"/></span><div class="min-w-0"><p class="break-words text-sm font-medium leading-6 text-slate-900">{{ __('workflow.'.$notification->data['message'], $notification->data['parameters'] ?? []) }}</p>@if(isset($notification->data['parameters']['status']))<div class="mt-2"><x-badge :value="$notification->data['parameters']['status']"/></div>@endif<p class="mt-2 text-xs text-slate-400">{{ $notification->created_at->format('Y-m-d H:i') }}</p><a class="text-link mt-3 inline-block text-xs" href="{{ route($notification->data['route'], $notification->data['record_id']) }}">{{ __('workflow.open_record') }}</a></div></div>@unless($notification->read_at)<form method="POST" action="{{ route('notifications.read', $notification->id) }}" x-data="{ submitting: false }" @submit="submitting = true">@csrf @method('PATCH')<button class="btn-secondary !text-xs" :disabled="submitting">{{ __('workflow.mark_read') }}</button></form>@endunless</li>@endforeach
        </ul><div class="p-5">{{ $notifications->links('components.pagination') }}</div>@endif
    </x-card>
</x-app-layout>
