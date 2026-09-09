@props(['value'])
<span @class(['inline-flex items-center gap-1.5 rounded-md px-2.5 py-1 text-xs font-medium',
'bg-emerald-50 text-emerald-700' => in_array($value, ['active','paid','occupied','completed','published']),
'bg-amber-50 text-amber-700' => in_array($value, ['pending','reserved','medium','under_review']),
'bg-red-50 text-red-700' => in_array($value, ['overdue','urgent','high','suspended','rejected']),
'bg-blue-50 text-blue-700' => in_array($value, ['assigned','in_progress','new']),
'bg-slate-100 text-slate-600' => !in_array($value, ['active','paid','occupied','completed','published','pending','reserved','medium','under_review','overdue','urgent','high','suspended','rejected','assigned','in_progress','new'])])><span class="h-1.5 w-1.5 rounded-full bg-current"></span>{{ __('app.'.$value) }}</span>