<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreKelasRequest;
use App\Http\Requests\Admin\UpdateKelasRequest;
use App\Models\Kelas; 
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Redirect;
use Inertia\Inertia;

class KelasController extends Controller
{
    // public function __construct()
    // {
    //     $this->middleware('can:manage_kelas');
    // }

    public function index(Request $request)
    {
        if (!$request->user()->can('manage_kelas')) {
            abort(403, 'Akses ditolak.');
        }

        $query = Kelas::orderBy('nama_kelas');

        if ($request->filled('search')) {
            $search = $request->input('search');
            $query->where('nama_kelas', 'LIKE', "%{$search}%")
                  ->orWhere('deskripsi', 'LIKE', "%{$search}%");
        }

        $kelasPaginator = $query->paginate(10)->withQueryString();

        // --- TITIK DEBUGGING ---
        // Opsi 1: Dump and Die untuk melihat seluruh objek paginator
        // dd($kelasPaginator);

        // Opsi 2: Dump and Die untuk melihat hanya item datanya
        // dd($kelasPaginator->items());

        // Opsi 3: Logging ke file (jika dd() mengganggu Inertia)
        // Log::info('Data Kelas dari Controller:', $kelasPaginator->toArray());
        // --- AKHIR TITIK DEBUGGING ---

        return Inertia::render('Admin/Kelas/Index', [
            'kelas' => $kelasPaginator->through(function ($item) {
                return [
                    'id_kelas' => $item->id_kelas,
                    'nama_kelas' => $item->nama_kelas,
                    'deskripsi' => $item->deskripsi,
                    'biaya_spp_default' => $item->biaya_spp_default,
                    'biaya_spp_default_formatted' => $item->biaya_spp_default ? number_format($item->biaya_spp_default, 0, ',', '.') : '-',
                ];
            }),
            'filters' => $request->only(['search']),
            'can' => [
                'create_kelas' => $request->user()->can('manage_kelas'),
                'edit_kelas' => $request->user()->can('manage_kelas'),
                'delete_kelas' => $request->user()->can('manage_kelas'),
            ]
        ]);
    }

    public function store(StoreKelasRequest $request)
    {
        $validated = $request->validated();
        Kelas::create($validated);

        return Redirect::route('admin.kelas.index')->with([
            'message' => 'Kelas berhasil dibuat.',
            'type' => 'success'
        ]);
    }

    public function update(UpdateKelasRequest $request, Kelas $kela)
    {
        $validated = $request->validated();
        $kela->update($validated);

        return Redirect::route('admin.kelas.index')->with([
            'message' => 'Kelas berhasil diperbarui.',
            'type' => 'success'
        ]);
    }

    public function destroy(Request $request, Kelas $kela)
    {
        if (!$request->user()->can('manage_kelas')) {
            abort(403);
        }

        if ($kela->siswa()->exists()) {
            return Redirect::back()->with([
                'message' => 'Kelas tidak dapat dihapus karena masih memiliki siswa.',
                'type' => 'error'
            ]);
        }

        $kela->delete();

        return Redirect::route('admin.kelas.index')->with([
            'message' => 'Kelas berhasil dihapus.',
            'type' => 'success'
        ]);
    }
}