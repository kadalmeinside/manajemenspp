<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Promo;
use App\Models\Kelas;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Storage;

class PromoController extends Controller
{
    /**
     * Menampilkan halaman manajemen promo.
     */
    public function index(Request $request)
    {
        $search = $request->query('search');

        $promos = Promo::with('kelas:id_kelas,nama_kelas')
            ->withCount('invoices')
            ->when($search, function($query, $search) {
                $query->where('nama_promo', 'like', "%{$search}%")
                      ->orWhere('kode_promo', 'like', "%{$search}%");
            })
            ->orderBy('created_at', 'desc')
            ->paginate(10)
            ->withQueryString();

        return Inertia::render('Admin/Promos/Index', [
            'pageTitle' => 'Manajemen Promo Pendaftaran',
            'promoList' => $promos,
            'filters'   => ['search' => $search],
            'allKelas'  => Kelas::orderBy('nama_kelas')->get(['id_kelas', 'nama_kelas']),
        ]);
    }

    /**
     * Menyimpan promo baru ke database.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'id_kelas' => 'nullable|uuid|exists:kelas,id_kelas',
            'nama_promo' => 'required|string|max:255',
            'kode_promo' => ['nullable', 'string', 'max:50', Rule::unique('promos')],
            'tipe_diskon' => ['required', Rule::in(['persen', 'tetap'])],
            'nilai_diskon' => 'required|numeric|min:0',
            'max_discount' => 'nullable|numeric|min:0',
            'max_uses' => 'nullable|integer|min:1',
            'max_uses_per_user' => 'nullable|integer|min:1',
            'tanggal_mulai' => 'required|date',
            'tanggal_berakhir' => 'nullable|date|after_or_equal:tanggal_mulai',
            'is_active' => 'required|boolean',
            'bukti_sk' => 'nullable|file|mimes:jpeg,png,jpg,pdf|max:2048',
        ]);

        if ($request->hasFile('bukti_sk')) {
            $validated['bukti_sk'] = $request->file('bukti_sk')->store('promos', 'public');
        }

        Promo::create($validated);

        return back()->with(['type' => 'success', 'message' => 'Promo baru berhasil dibuat.']);
    }

    /**
     * Memperbarui data promo yang ada.
     */
    public function update(Request $request, Promo $promo)
    {
        $validated = $request->validate([
            'id_kelas' => 'nullable|uuid|exists:kelas,id_kelas',
            'nama_promo' => 'required|string|max:255',
            'kode_promo' => ['nullable', 'string', 'max:50', Rule::unique('promos')->ignore($promo->id)],
            'tipe_diskon' => ['required', Rule::in(['persen', 'tetap'])],
            'nilai_diskon' => 'required|numeric|min:0',
            'max_discount' => 'nullable|numeric|min:0',
            'max_uses' => 'nullable|integer|min:1',
            'max_uses_per_user' => 'nullable|integer|min:1',
            'tanggal_mulai' => 'required|date',
            'tanggal_berakhir' => 'nullable|date|after_or_equal:tanggal_mulai',
            'is_active' => 'required|boolean',
            'bukti_sk' => 'nullable|file|mimes:jpeg,png,jpg,pdf|max:2048',
        ]);

        if ($request->hasFile('bukti_sk')) {
            // Hapus file lama jika ada
            if ($promo->bukti_sk) {
                Storage::disk('public')->delete($promo->bukti_sk);
            }
            $validated['bukti_sk'] = $request->file('bukti_sk')->store('promos', 'public');
        }

        $promo->update($validated);

        return back()->with(['type' => 'success', 'message' => 'Promo berhasil diperbarui.']);
    }

    /**
     * Menghapus promo dari database.
     */
    public function destroy(Promo $promo)
    {
        $promo->delete();
        return back()->with(['type' => 'success', 'message' => 'Promo berhasil dihapus.']);
    }
}
