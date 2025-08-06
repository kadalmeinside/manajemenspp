<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Kelas; 
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Redirect;
use Inertia\Inertia;
use Illuminate\Validation\Rule;

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
                    'biaya_pendaftaran' => $item->biaya_pendaftaran, // <-- PERBAIKAN: Data ini sekarang dikirim
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

    public function store(Request $request)
    {
        if (!$request->user()->can('manage_kelas')) {
            abort(403);
        }

        // Menggunakan StoreKelasRequest yang sudah benar dari Canvas Anda
        $validated = $request->validate([
            'nama_kelas' => ['required', 'string', 'max:100', Rule::unique('kelas', 'nama_kelas')],
            'deskripsi' => 'nullable|string|max:1000',
            'biaya_spp_default' => 'nullable|numeric|min:0',
            'biaya_pendaftaran' => 'nullable|numeric|min:0',
        ]);

        Kelas::create($validated);

        return Redirect::route('admin.kelas.index')->with('flash', [
            'type' => 'success',
            'message' => 'Kelas berhasil dibuat.'
        ]);
    }

    public function update(Request $request, Kelas $kelas)
    {
        if (!$request->user()->can('manage_kelas')) {
            abort(403);
        }
        
        $validated = $request->validate([
            'nama_kelas' => ['required', 'string', 'max:255', Rule::unique('kelas')->ignore($kelas->id_kelas, 'id_kelas')],
            'deskripsi' => 'nullable|string',
            'biaya_spp_default' => 'nullable|numeric|min:0',
            'biaya_pendaftaran' => 'nullable|numeric|min:0',
        ]);
        
        $kelas->update($validated);

        return Redirect::route('admin.kelas.index')->with('flash', [
            'type' => 'success',
            'message' => 'Kelas berhasil diperbarui.'
        ]);
    }

    public function destroy(Request $request, Kelas $kelas)
    {
        if (!$request->user()->can('manage_kelas')) {
            abort(403);
        }

        if ($kelas->siswa()->exists()) {
            return Redirect::back()->with('flash', [
                'type' => 'error',
                'message' => 'Kelas tidak dapat dihapus karena masih memiliki siswa.'
            ]);
        }

        $kelas->delete();

        return Redirect::route('admin.kelas.index')->with('flash', [
            'type' => 'success',
            'message' => 'Kelas berhasil dihapus.'
        ]);
    }
}
