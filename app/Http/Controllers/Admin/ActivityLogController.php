<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Spatie\Activitylog\Models\Activity;

class ActivityLogController extends Controller
{
    public function index()
    {
        if (!auth()->user()->hasRole('admin')) {
            abort(403);
        }

        return Inertia::render('Admin/ActivityLog/Index', [
            'pageTitle' => 'Log Aktivitas Sistem',
            'activities' => Activity::with(['causer', 'subject'])
                ->latest()
                ->paginate(20)
                ->through(fn ($log) => [
                    'id' => $log->id,
                    'description' => $log->description,
                    'subject_type' => $log->subject_type ? class_basename($log->subject_type) : '-',
                    'causer_name' => $log->causer?->name ?? 'Sistem',
                    'created_at' => $log->created_at->isoFormat('D MMM YYYY, HH:mm:ss'),
                ]),
        ]);
    }
}
