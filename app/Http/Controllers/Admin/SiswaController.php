<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreSiswaRequest;
use App\Http\Requests\Admin\UpdateSiswaRequest;
use App\Models\Siswa;
use App\Models\User;
use App\Models\Kelas;
use App\Models\Invoice;
use App\Exports\SiswaExport;
use App\Imports\SiswaImport;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Redirect;
use Inertia\Inertia;
use Spatie\Permission\Models\Role;
use Illuminate\Support\Str;
use Carbon\Carbon;
use Maatwebsite\Excel\Facades\Excel;

class SiswaController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        if (!$request->user()->can('manage_siswa')) {
            abort(403);
        }

        $user = $request->user();
        $query = Siswa::with(['kelas', 'user'])->orderBy('nama_siswa');

        if ($user->hasRole('admin_kelas')) {
            $managedKelasIds = $user->managedClasses()->pluck('kelas.id_kelas');
            $query->whereIn('id_kelas', $managedKelasIds);
        }

        if ($request->filled('search')) {
            $search = $request->input('search');
            $query->where(function ($q) use ($search) {
                $q->where('nama_siswa', 'LIKE', "%{$search}%")
                  ->orWhereHas('user', function ($userQuery) use ($search) {
                      $userQuery->where('email', 'LIKE', "%{$search}%")
                                ->orWhere('name', 'LIKE', "%{$search}%");
                  })
                  ->orWhereHas('kelas', function ($kelasQuery) use ($search) {
                      $kelasQuery->where('nama_kelas', 'LIKE', "%{$search}%");
                  });
            });
        }

        if ($request->filled('kelas_id') && $request->input('kelas_id') !== '') {
            $query->where('id_kelas', $request->input('kelas_id'));
        }

        $siswaList = $query->paginate(10)->withQueryString();
        $allKelasQuery = Kelas::orderBy('nama_kelas');
        if ($user->hasRole('admin_kelas')) {
            $allKelasQuery->whereIn('id_kelas', $user->managedClasses()->pluck('kelas.id_kelas'));
        }

        return Inertia::render('Admin/Siswa/Index', [
            'siswaList' => $siswaList->through(fn($siswa) => [
                'id_siswa' => $siswa->id_siswa,
                'nama_siswa' => $siswa->nama_siswa,
                'status_siswa' => $siswa->status_siswa,
                'email_wali' => $siswa->user?->email,
                'kelas_nama' => $siswa->kelas?->nama_kelas,
                'tanggal_bergabung_formatted' => $siswa->tanggal_bergabung->isoFormat('D MMM YYYY'),
                'full_data_for_edit' => $this->getSiswaDataForEdit($siswa),
            ]),
            'filters' => $request->only(['search', 'kelas_id']),
            'allKelas' => $allKelasQuery->get(['id_kelas', 'nama_kelas']),
            'can' => [
                'create_siswa' => $request->user()->can('manage_siswa'),
                'edit_siswa' => $request->user()->can('manage_siswa'),
                'delete_siswa' => $request->user()->can('manage_siswa'),
            ]
        ]);
    }

    private function getSiswaDataForEdit(Siswa $siswa)
    {
        $siswa->load('user');
        return [
            'id_siswa' => $siswa->id_siswa,
            'nama_siswa' => $siswa->nama_siswa,
            'status_siswa' => $siswa->status_siswa,
            'id_kelas' => $siswa->id_kelas,
            'nomor_telepon_wali' => $siswa->nomor_telepon_wali,
            'tanggal_lahir' => $siswa->tanggal_lahir?->format('Y-m-d'),
            'tanggal_bergabung' => $siswa->tanggal_bergabung->format('Y-m-d'),
            'jumlah_spp_custom' => $siswa->jumlah_spp_custom,
            'admin_fee_custom' => $siswa->admin_fee_custom,
            'user' => [
                'id' => $siswa->user?->id,
                'name' => $siswa->user?->name,
                'email' => $siswa->user?->email,
            ]
        ];
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreSiswaRequest $request)
    {
        $validated = $request->validated();
        $siswaRole = Role::where('name', 'siswa')->firstOrFail();
        
        $newNis = ''; // Inisialisasi variabel NIS

        // Gunakan transaksi untuk mencegah race condition saat membuat NIS
        DB::transaction(function () use ($validated, $siswaRole, $request, &$newNis) {
            
            // --- Logika Generate NIS ---
            $kelas = Kelas::findOrFail($validated['id_kelas']);
            $tahun = now()->format('Y');
            $kodeCabang = $kelas->kode_cabang ?? 'XXX';

            // Kunci tabel untuk mencegah user lain membaca saat kita menghitung nomor urut
            $lastSiswa = Siswa::where('nis', 'LIKE', "{$tahun}-{$kodeCabang}-%")
                              ->lockForUpdate() // Ini bagian penting untuk mencegah race condition
                              ->orderBy('nis', 'desc')
                              ->first();

            $nomorUrut = 1;
            if ($lastSiswa) {
                $lastNomorUrut = (int) substr($lastSiswa->nis, -4);
                $nomorUrut = $lastNomorUrut + 1;
            }
            $nomorUrutFormatted = str_pad($nomorUrut, 4, '0', STR_PAD_LEFT);
            $newNis = "{$tahun}{$kodeCabang}{$nomorUrutFormatted}";
            // --- Akhir Logika Generate NIS ---

            // Buat User
            $user = User::create([
                'name' => $validated['user_name'],
                'email' => $validated['email_wali'],
                'password' => Hash::make($validated['user_password']),
                'email_verified_at' => now(),
            ]);
            $user->assignRole($siswaRole);

            // Buat Siswa dengan NIS yang baru
            Siswa::create([
                'nis' => $newNis, // Simpan NIS yang sudah digenerate
                'nama_siswa' => $validated['nama_siswa'],
                'tanggal_lahir' => $validated['tanggal_lahir'],
                'status_siswa' => $validated['status_siswa'],
                'id_kelas' => $validated['id_kelas'],
                'id_user' => $user->id,
                'email_wali' => $validated['email_wali'],
                'nomor_telepon_wali' => $validated['nomor_telepon_wali'],
                'tanggal_bergabung' => $validated['tanggal_bergabung'],
                'jumlah_spp_custom' => $validated['jumlah_spp_custom'],
                'admin_fee_custom' => $validated['admin_fee_custom'],
            ]);
        });

        return Redirect::route('admin.siswa.index')->with('message', 'Siswa baru dengan NIS ' . $newNis . ' berhasil dibuat.');
    }
    /**
     * Display the specified resource.
     */
    public function show(Request $request, Siswa $siswa)
    {
        //$this->authorize('manage_siswa');
        if (!$request->user()->can('manage_siswa')) {
            abort(403);
        }
        $siswa->load(['user', 'kelas']);
        
        $availableYears = $siswa->invoices()
            ->whereNotNull('periode_tagihan')
            ->select(DB::raw('YEAR(periode_tagihan) as year'))
            ->distinct()->orderBy('year', 'desc')->pluck('year');
        
        $selectedTahun = $request->input('tahun', $availableYears->first() ?? now()->year);
        $selectedType = $request->input('type', '');

        $invoicesQuery = $siswa->invoices()->orderBy('created_at', 'desc');
        
        if ($selectedType === 'spp') {
            $invoicesQuery->where('type', 'spp')->whereYear('periode_tagihan', $selectedTahun);
        } elseif ($selectedType) {
            $invoicesQuery->where('type', $selectedType);
        }
        
        $invoices = $invoicesQuery->get();
        
        return Inertia::render('Admin/Siswa/Show', [
            'pageTitle' => 'Detail Siswa',
            'siswa' => $this->formatSiswaForDetail($siswa),
            'invoices' => $invoices->map(fn($invoice) => $this->formatInvoiceForDetail($invoice)),
            'filters' => ['tahun' => (int)$selectedTahun, 'type' => $selectedType],
            'availableYears' => $availableYears,
        ]);
    }

    private function formatSiswaForDetail(Siswa $siswa) {
        return [
            'id_siswa' => $siswa->id_siswa,
            'nama_siswa' => $siswa->nama_siswa,
            'tanggal_lahir_formatted' => $siswa->tanggal_lahir?->isoFormat('D MMMM YYYY'),
            'status_siswa' => $siswa->status_siswa,
            'kelas_nama' => $siswa->kelas?->nama_kelas,
            'email_wali' => $siswa->user?->email,
            'nomor_telepon_wali' => $siswa->nomor_telepon_wali,
            'tanggal_bergabung_formatted' => $siswa->tanggal_bergabung->isoFormat('D MMMM YYYY'),
        ];
    }

    private function formatInvoiceForDetail(Invoice $invoice) {
        return [
            'id' => $invoice->id,
            'description' => $invoice->description,
            'total_amount_formatted' => 'Rp ' . number_format($invoice->total_amount, 0, ',', '.'),
            'status' => $invoice->status,
            'due_date_formatted' => Carbon::parse($invoice->due_date)->isoFormat('D MMM YY'),
            'paid_at_formatted' => $invoice->paid_at ? Carbon::parse($invoice->paid_at)->isoFormat('D MMM YY, HH:mm') : '-',
            'xendit_payment_url' => $invoice->xendit_payment_url,
            'can_pay' => in_array($invoice->status, ['PENDING']),
        ];
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Siswa $siswa)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateSiswaRequest $request, Siswa $siswa)
    {
        $validated = $request->validated();

        DB::transaction(function () use ($validated, $siswa, $request) {
            if ($siswa->user) {
                $siswa->user->update([
                    'name' => $validated['user_name'],
                    'email' => $validated['email_wali'],
                ]);
                if (!empty($validated['user_password'])) {
                    $siswa->user->update(['password' => Hash::make($validated['user_password'])]);
                }
            }

            $siswa->update($request->only([
                'nis','nama_siswa', 'tanggal_lahir', 'status_siswa', 'id_kelas',
                'email_wali', 'nomor_telepon_wali', 'tanggal_bergabung',
                'jumlah_spp_custom', 'admin_fee_custom'
            ]));
        });

        return Redirect::route('admin.siswa.index')->with('message', 'Data siswa berhasil diperbarui.')->with('type', 'success');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Siswa $siswa)
    {
        $this->authorize('manage_siswa');

        DB::transaction(function () use ($siswa) {
            if ($siswa->user) {
                $siswa->user->delete();
            }
            $siswa->delete();
        });

        return Redirect::route('admin.siswa.index')->with([
            'message' => 'Siswa berhasil dihapus.',
            'type' => 'success'
        ]);
    }

    /**
     * ekspor data Excel.
     */
    public function export()
    {
        if (!auth()->user()->can('manage_siswa')) {
            abort(403);
        }

        return Excel::download(new SiswaExport, 'data-siswa-'.date('Y-m-d').'.xlsx');
    }

    public function import(Request $request)
    {
        if (!auth()->user()->can('manage_siswa')) {
            abort(403);
        }

        $request->validate([
            'file_import' => 'required|mimes:xlsx,xls',
        ]);

        try {
            Excel::import(new SiswaImport, $request->file('file_import'));
        } catch (\Maatwebsite\Excel\Validators\ValidationException $e) {
             $failures = $e->failures();
             $errorMessages = [];
             foreach ($failures as $failure) {
                 $errorMessages[] = "Baris {$failure->row()}: {$failure->errors()[0]} untuk atribut '{$failure->attribute()}'";
             }
             return Redirect::back()->with([
                'message' => 'Gagal mengimpor data. Terdapat beberapa kesalahan: ' . implode(', ', $errorMessages),
                'type' => 'error'
            ]);
        } catch (\Exception $e) {
            return Redirect::back()->with([
                'message' => 'Terjadi kesalahan saat memproses file Anda: ' . $e->getMessage(),
                'type' => 'error'
            ]);
        }


        return Redirect::route('admin.siswa.index')->with([
            'message' => 'Data siswa berhasil diimpor.',
            'type' => 'success'
        ]);
    }

    public function generateNis(Kelas $kelas)
    {
        if (!auth()->user()->can('manage_siswa')) {
            abort(403);
        }

        $tahun = now()->format('Y'); // 2 digit tahun
        $kodeCabang = $kelas->kode_cabang ?? 'N/A';
        
        $lastSiswa = Siswa::where('nis', 'LIKE', "{$tahun}-{$kodeCabang}-%")
                          ->orderBy('nis', 'desc')
                          ->first();

        $nomorUrut = 1;
        if ($lastSiswa) {
            $lastNomorUrut = (int) substr($lastSiswa->nis, -4);
            $nomorUrut = $lastNomorUrut + 1;
        }
        
        $nomorUrutFormatted = str_pad($nomorUrut, 4, '0', STR_PAD_LEFT);
        $newNis = "{$tahun}{$kodeCabang}{$nomorUrutFormatted}";

        return response()->json(['nis' => $newNis]);
    }
}
