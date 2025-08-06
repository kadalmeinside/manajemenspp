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

    public function store(Request $request)
    {
        if (!$request->user()->can('manage_kelas')) {
            abort(403);
        }

        $validated = $request->validate([
            'nama_kelas' => ['required', 'string', 'max:255', Rule::unique('kelas')],
            'deskripsi' => 'nullable|string',
            'biaya_spp_default' => 'nullable|numeric|min:0',
            'biaya_pendaftaran' => 'nullable|numeric|min:0',
        ]);

        Kelas::create($validated);

        // ### PERBAIKAN ###
        // Mengirim flash message sebagai session terpisah
        return Redirect::route('admin.kelas.index')
            ->with('type', 'success')
            ->with('message', 'Kelas berhasil dibuat.');
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

        // ### PERBAIKAN ###
        // Mengirim flash message sebagai session terpisah
        return Redirect::route('admin.kelas.index')
            ->with('type', 'success')
            ->with('message', 'Kelas berhasil diperbarui.');
    }

    public function destroy(Request $request, Kelas $kelas)
    {
        if (!$request->user()->can('manage_kelas')) {
            abort(403);
        }

        if ($kelas->siswa()->exists()) {
            // ### PERBAIKAN ###
            // Mengirim flash message sebagai session terpisah
            return Redirect::back()
                ->with('type', 'error')
                ->with('message', 'Kelas tidak dapat dihapus karena masih memiliki siswa.');
        }

        $kelas->delete();

        // ### PERBAIKAN ###
        // Mengirim flash message sebagai session terpisah
        return Redirect::route('admin.kelas.index')
            ->with('type', 'success')
            ->with('message', 'Kelas berhasil dihapus.');
    }
}
