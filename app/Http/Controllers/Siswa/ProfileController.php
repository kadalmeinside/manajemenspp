<?php

namespace App\Http\Controllers\Siswa;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use App\Models\Siswa;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;
use Illuminate\Validation\Rule;

class ProfileController extends Controller
{
    /**
     * Menampilkan halaman profil siswa yang sedang login.
     */
    public function show(Request $request): Response
    {
        // Otorisasi sederhana berdasarkan role atau permission
        // Permission 'view_own_siswa_data' sudah kita buat sebelumnya
        if (!$request->user()->can('view_own_siswa_data')) {
            abort(403, 'AKSES DITOLAK.');
        }

        $siswa = $request->user()->siswa()->with('kelas')->firstOrFail();

        // Handle jika akun user ini tidak terhubung dengan data siswa manapun
        if (!$siswa) {
            return Inertia::render('Siswa/Profile', [
                'pageTitle' => 'Profil Siswa',
                'error' => 'Data siswa tidak ditemukan untuk akun ini. Silakan hubungi admin.'
            ]);
        }

        // Kirim data ke komponen Vue
        return Inertia::render('Siswa/Profile', [
            'siswa' => [
                'nama_siswa' => $siswa->nama_siswa,
                'status_siswa' => $siswa->status_siswa,
                'email_wali' => $siswa->email_wali,
                'nomor_telepon_wali' => $siswa->nomor_telepon_wali,
                'tanggal_lahir_formatted' => $siswa->tanggal_lahir?->isoFormat('D MMMM YYYY'),
                'tanggal_bergabung_formatted' => $siswa->tanggal_bergabung->isoFormat('D MMMM YYYY'),
                'jumlah_spp_custom_formatted' => $siswa->jumlah_spp_custom ? 'Rp ' . number_format($siswa->jumlah_spp_custom, 0, ',', '.') : '-',
                'admin_fee_custom_formatted' => $siswa->admin_fee_custom ? 'Rp ' . number_format($siswa->admin_fee_custom, 0, ',', '.') : '-',
                'kelas' => $siswa->kelas ? [ // Kirim data kelas jika ada
                    'nama_kelas' => $siswa->kelas->nama_kelas,
                    'biaya_spp_default_formatted' => $siswa->kelas->biaya_spp_default ? 'Rp ' . number_format($siswa->kelas->biaya_spp_default, 0, ',', '.') : '-',
                ] : null,
            ],
            'pageTitle' => 'Profil Saya',
        ]);
    }

    public function updateInformation(Request $request)
    {
        $user = $request->user();
        $siswa = $user->siswa;

        $validated = $request->validate([
            'email_wali' => ['required', 'string', 'email', 'max:255', Rule::unique('users', 'email')->ignore($user->id)],
            'nomor_telepon_wali' => ['required', 'string', 'max:20'],
        ]);

        $user->forceFill([
            'email' => $validated['email_wali'],
        ])->save();

        $siswa->forceFill([
            'email_wali' => $validated['email_wali'],
            'nomor_telepon_wali' => $validated['nomor_telepon_wali'],
        ])->save();

        return back()->with([
            'message' => 'Informasi kontak berhasil diperbarui.',
            'type' => 'success'
        ]);
    }

    /**
     * Memperbarui password user.
     */
    public function updatePassword(Request $request)
    {
        $validated = $request->validate([
            'current_password' => ['required', 'current_password'],
            'password' => ['required', Password::defaults(), 'confirmed'],
        ]);

        $request->user()->update([
            'password' => Hash::make($validated['password']),
        ]);

        return back()->with([
            'message' => 'Password berhasil diperbarui.',
            'type' => 'success'
        ]);
    }
}