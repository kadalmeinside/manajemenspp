<?php

namespace App\Http\Middleware;

use Illuminate\Http\Request;
use Inertia\Middleware;
use Tighten\Ziggy\Ziggy;
use Illuminate\Support\Facades\Cache;
use App\Models\Setting;
use App\Models\StudentLeave;

class HandleInertiaRequests extends Middleware
{
    /**
     * The root template that is loaded on the first page visit.
     *
     * @var string
     */
    protected $rootView = 'app';

    /**
     * Determine the current asset version.
     */
    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    /**
     * Define the props that are shared by default.
     *
     * @return array<string, mixed>
     */
    public function share(Request $request): array
    {
        return array_merge(parent::share($request), [
            'auth' => [
                'user' => $request->user() ? [
                    'id' => $request->user()->id,
                    'name' => $request->user()->name,
                    // 'email' => $request->user()->email,
                    'roles' => $request->user()->getRoleNames(),
                    'permissions' => $request->user()->getAllPermissions()->pluck('name'), // KIRIM PERMISSIONS
                    'siswas' => $request->user()->hasRole('siswa') ? $request->user()->siswas()->whereNotIn('status_siswa', ['Keluar', 'Non-Aktif'])->get(['id_siswa', 'nama_siswa'])->toArray() : [],
                    'active_siswa_id' => session('active_siswa_id') ?: ($request->user()->hasRole('siswa') ? optional($request->user()->siswas()->whereNotIn('status_siswa', ['Keluar', 'Non-Aktif'])->first())->id_siswa : null),
                ] : null,
            ],
            'missing_agreements' => function () use ($request) {
                if ($request->user() && $request->user()->hasRole('siswa')) {
                    $settings = \App\Models\Setting::whereIn('key', [
                        'legal_doc_registration_academy',
                        'legal_doc_registration_ss',
                        'legal_doc_registration_public',
                    ])->pluck('value', 'key');

                    $siswas = $request->user()->siswas()->with('kelas')->get();
                    $missingGroups = [];

                    foreach ($siswas as $siswa) {
                        $requiredDocId = null;
                        
                        if ($siswa->kelas) {
                            if ($siswa->kelas->nama_kelas === 'Persija Academy') {
                                $requiredDocId = $settings['legal_doc_registration_academy'] ?? null;
                            } elseif ($siswa->kelas->deskripsi === 'Soccer School') {
                                $requiredDocId = $settings['legal_doc_registration_ss'] ?? null;
                            } else {
                                $requiredDocId = $settings['legal_doc_registration_public'] ?? null;
                            }
                        }

                        // Fallback to latest active terms_and_conditions if settings are empty
                        if (!$requiredDocId) {
                            $fallback = \App\Models\LegalDocument::where('type', 'terms_and_conditions')
                                            ->where('is_active', true)
                                            ->latest('version')
                                            ->first();
                            $requiredDocId = $fallback ? $fallback->id : null;
                        }

                        if ($requiredDocId) {
                            // Cek apakah siswa ini sudah menyetujui dokumen tersebut
                            $hasAgreed = \App\Models\UserAgreement::where('id_siswa', $siswa->id_siswa)
                                ->where('legal_document_id', $requiredDocId)
                                ->exists();

                            if (!$hasAgreed) {
                                if (!isset($missingGroups[$requiredDocId])) {
                                    $doc = \App\Models\LegalDocument::find($requiredDocId);
                                    if ($doc) {
                                        $missingGroups[$requiredDocId] = [
                                            'document' => $doc,
                                            'siswa' => [],
                                        ];
                                    }
                                }
                                
                                if (isset($missingGroups[$requiredDocId])) {
                                    $missingGroups[$requiredDocId]['siswa'][] = [
                                        'id_siswa' => $siswa->id_siswa,
                                        'nama_siswa' => $siswa->nama_siswa,
                                    ];
                                }
                            }
                        }
                    }

                    // Kembalikan grup pertama yang ditemukan
                    if (!empty($missingGroups)) {
                        return array_values($missingGroups)[0];
                    }
                }
                return null;
            },
            'ziggy' => function () use ($request) {
                $ziggy = new Ziggy(null, $request->url());

                // Jika user adalah admin, berikan semua rute.
                // Jika tidak, berikan hanya rute dari grup 'public'.
                if (! $request->user() || !$request->user()->hasRole('admin|user')) {
                    $ziggy->filter(config('ziggy.groups.public'));
                }

                return $ziggy->toArray();
            },
            'flash' => [ // Pastikan flash message di-handle
                'message' => fn () => $request->session()->get('message'),
                'type' => fn () => $request->session()->get('type'),
                'success' => fn () => $request->session()->get('success'),
                'completed_data' => fn () => $request->session()->get('completed_data'),
                'key' => fn () => $request->session()->get('key'), 
            ],
            'app_settings' => function () {
                return Cache::rememberForever('app_settings', function () {
                    return Setting::all()->pluck('value', 'key');
                });
            },
            // Badge notifikasi cuti — hanya untuk admin yang punya akses
            'pending_leaves_count' => function () use ($request) {
                if ($request->user() && $request->user()->can('manage_all_tagihan')) {
                    return StudentLeave::where('status', 'pending')->count();
                }
                return 0;
            },
        ]);
    }
}
