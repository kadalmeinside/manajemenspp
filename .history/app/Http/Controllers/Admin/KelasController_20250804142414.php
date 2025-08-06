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

        return Inertia::render('Admin/Kelas/Index', [
            'kelas' => $kelasPaginator->through(function ($item) {
                return [
                    'id_kelas' => $item->id_kelas,
                    'nama_kelas' => $item->nama_kelas,
                    'deskripsi' => $item->deskripsi,
                    'biaya_spp_default' => $item->biaya_spp_default,
                    'biaya_pendaftaran' => $item->biaya_pendaftaran,
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
        $request->merge([
            'biaya_spp_default' => preg_replace('/[^0-9]/', '', $request->input('biaya_spp_default')),
            'biaya_pendaftaran' => preg_replace('/[^0-9]/', '', $request->input('biaya_pendaftaran')),
        ]);
        
        $validated = $request->validated();
        Kelas::create($validated);

        // ### PERBAIKAN ###
        // Mengirim flash message sebagai session terpisah
        return Redirect::route('admin.kelas.index')
            ->with('type', 'success')
            ->with('message', 'Kelas berhasil dibuat.');
    }

    public function update(UpdateKelasRequest $request, Kelas $kela)
    {
        $request->merge([
            'biaya_spp_default' => preg_replace('/[^0-9]/', '', $request->input('biaya_spp_default')),
            'biaya_pendaftaran' => preg_replace('/[^0-9]/', '', $request->input('biaya_pendaftaran')),
        ]);

        $validated = $request->validated();
        $kela->update($validated);

        // ### PERBAIKAN ###
        // Mengirim flash message sebagai session terpisah
        return Redirect::route('admin.kelas.index')
            ->with('type', 'success')
            ->with('message', 'Kelas berhasil diperbarui.');
    }

    public function destroy(Request $request, Kelas $kela)
    {
        if (!$request->user()->can('manage_kelas')) {
            abort(403);
        }

        if ($kela->siswa()->exists()) {
            // ### PERBAIKAN ###
            // Mengirim flash message sebagai session terpisah
            return Redirect::back()
                ->with('type', 'error')
                ->with('message', 'Kelas tidak dapat dihapus karena masih memiliki siswa.');
        }

        $kela->delete();

        return Redirect::back()
            ->with('type', 'success')
            ->with('message', 'Kelas berhasil dihapus.');
    }
}
