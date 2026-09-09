<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class NotificationController extends Controller
{
    public function index(Request $request): View
    {
        return view('notifications.index', ['notifications' => $request->user()->notifications()->latest()->paginate(15)]);
    }

    public function read(Request $request, string $notification): RedirectResponse
    {
        $notice = $request->user()->notifications()->findOrFail($notification);
        $notice->markAsRead();

        return back()->with('success', __('workflow.notification_read'));
    }
}
