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
                'vapid_public_key' => config('webpush.vapid.public_key'),
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
                                            ->whereNotNull('published_at')
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
                // Gunakan cache pendek atau non-forever agar versi bisa update tanpa perlu clear cache manual
                $settings = Cache::remember('app_settings_db', 60, function () {
                    return Setting::all()->pluck('value', 'key')->toArray();
                });

                // Auto-detect version dari composer.json
                try {
                    $composerPath = base_path('composer.json');
                    if (file_exists($composerPath)) {
                        $composer = json_decode(file_get_contents($composerPath), true);
                        if (isset($composer['version'])) {
                            $settings['app_version'] = $composer['version'];
                        }
                    }
                } catch (\Exception $e) {}

                // Auto-detect build dari Git commit hash
                try {
                    $gitPath = base_path('.git');
                    if (is_dir($gitPath)) {
                        $head = trim(@file_get_contents($gitPath . '/HEAD'));
                        if (strpos($head, 'ref: ') === 0) {
                            $ref = substr($head, 5);
                            $commit = trim(@file_get_contents($gitPath . '/' . $ref));
                            $build = substr($commit, 0, 7);
                        } else {
                            $build = substr($head, 0, 7);
                        }
                        
                        if ($build) {
                            $settings['app_build'] = $build;
                        }
                    }
                } catch (\Exception $e) {}

                return $settings;
            },
            // Badge notifikasi cuti — hanya untuk admin yang punya akses.
            // Cache 30 detik per user untuk mengurangi query DB pada setiap request.
            'pending_leaves_count' => function () use ($request) {
                if ($request->user() && $request->user()->can('manage_all_tagihan')) {
                    return Cache::remember(
                        'pending_leaves_count_u' . $request->user()->id,
                        30,
                        fn () => StudentLeave::where('status', 'pending')->count()
                    );
                }
                return 0;
            },
            'pending_aktivasi_spp_count' => function () use ($request) {
                if ($request->user() && $request->user()->can('view_siswa')) {
                    return Cache::remember(
                        'pending_aktivasi_spp_count_u' . $request->user()->id,
                        30,
                        function () use ($request) {
                            $query = \App\Models\Siswa::whereNull('mulai_spp_date')
                                ->whereHas('invoices', function ($q) {
                                    $q->where('type', 'pendaftaran')->where('status', 'PAID');
                                });
                            if ($request->user()->hasRole('admin_kelas')) {
                                $managedKelasIds = $request->user()->managedClasses()->pluck('kelas.id_kelas');
                                $query->whereIn('id_kelas', $managedKelasIds);
                            }
                            return $query->count();
                        }
                    );
                }
                return 0;
            },
            'cart_count' => function () use ($request) {
                if ($request->user() && $request->user()->hasRole('siswa')) {
                    $cart = \App\Models\Cart::where('user_id', $request->user()->id)->first();
                    return $cart ? $cart->items()->sum('quantity') : 0;
                }
                return 0;
            },
        ]);
    }
}
