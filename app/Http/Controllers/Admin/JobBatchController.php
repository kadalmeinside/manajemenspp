<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\JobBatch;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Str;

class JobBatchController extends Controller
{
    public function index(Request $request)
    {
        if (!$request->user()->can('manage application settings')) {
            abort(403, 'Akses Ditolak.');
        }

        $jobBatches = JobBatch::with('user:id,name')
            ->latest() 
            ->paginate(15)
            ->withQueryString();

        return Inertia::render('Admin/Jobs/Index', [
            'jobBatches' => $jobBatches->through(function ($batch) {
                return [
                    'id' => $batch->id,
                    'name' => $batch->name,
                    'status' => $batch->status,
                    'user_name' => $batch->user->name ?? 'N/A',
                    'created_at' => $batch->created_at->isoFormat('D MMM YY, HH:mm'),
                    'started_at' => $batch->started_at?->isoFormat('D MMM YY, HH:mm'),
                    'finished_at' => $batch->finished_at?->isoFormat('D MMM YY, HH:mm'),
                    'total_items' => $batch->total_items,
                    'processed_items' => $batch->processed_items,
                    'progress' => $batch->total_items > 0 ? (int)(($batch->processed_items / $batch->total_items) * 100) : 0,
                    'output' => Str::limit($batch->output, 100),
                ];
            }),
            'pageTitle' => 'Riwayat Proses Latar Belakang',
        ]);
    }
}
