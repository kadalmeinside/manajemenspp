<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Promo;
use App\Models\Kelas;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Validation\Rule;

class PromoController extends Controller
{
    /**
     * Menampilkan halaman manajemen promo.
     */
    public function index(Request $request)
    {
        $promos = Promo::with('kelas:id_kelas,nama_kelas')
            ->orderBy('created_at', 'desc')
            ->paginate(10)
            ->withQueryString();

        return Inertia::render('Admin/Promos/Index', [
            'pageTitle' => 'Manajemen Promo Pendaftaran',
            'promoList' => $promos,
            'allKelas' => Kelas::orderBy('nama_kelas')->get(['id_kelas', 'nama_kelas']),
        ]);
    }

    /**
     * Menyimpan promo baru ke database.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'id_kelas' => 'required|uuid|exists:kelas,id_kelas',
            'nama_promo' => 'required|string|max:255',
            'kode_promo' => ['nullable', 'string', 'max:50', Rule::unique('promos')],
            'tipe_diskon' => ['required', Rule::in(['persen', 'tetap'])],
            'nilai_diskon' => 'required|numeric|min:0',
            'tanggal_mulai' => 'required|date',
            'tanggal_berakhir' => 'nullable|date|after_or_equal:tanggal_mulai',
            'is_active' => 'required|boolean',
        ]);

        Promo::create($validated);

        return back()->with('flash', ['type' => 'success', 'message' => 'Promo baru berhasil dibuat.']);
    }

    /**
     * Memperbarui data promo yang ada.
     */
    public function update(Request $request, Promo $promo)
    {
        $validated = $request->validate([
            'id_kelas' => 'required|uuid|exists:kelas,id_kelas',
            'nama_promo' => 'required|string|max:255',
            'kode_promo' => ['nullable', 'string', 'max:50', Rule::unique('promos')->ignore($promo->id)],
            'tipe_diskon' => ['required', Rule::in(['persen', 'tetap'])],
            'nilai_diskon' => 'required|numeric|min:0',
            'tanggal_mulai' => 'required|date',
            'tanggal_berakhir' => 'nullable|date|after_or_equal:tanggal_mulai',
            'is_active' => 'required|boolean',
        ]);

        $promo->update($validated);

        return back()->with('flash', ['type' => 'success', 'message' => 'Promo berhasil diperbarui.']);
    }

    /**
     * Menghapus promo dari database.
     */
    public function destroy(Promo $promo)
    {
        $promo->delete();
        return back()->with('flash', ['type' => 'success', 'message' => 'Promo berhasil dihapus.']);
    }
}
