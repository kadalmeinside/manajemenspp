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
use App\Traits\HandlesActiveSiswa;

class ProfileController extends Controller
{
    use HandlesActiveSiswa;
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

        $user = $request->user();
        $siswa = $this->getActiveSiswa($user);
        
        if ($siswa) {
            $siswa->load(['kelas', 'mutasiSiswas.fromKelas', 'mutasiSiswas.toKelas']);
        }

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
                'id_siswa' => $siswa->id_siswa,
                'nis' => $siswa->nis,
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
                'foto_url' => $siswa->foto_url,
            ],
            'id_card_back_text' => \App\Models\Setting::where('key', 'id_card_back_text')->value('value') ?? 'LOREM IPSUM DOLOR SIT AMET, CONSECTETUR ADIPISCING ELIT, SED DO EIUSMOD TEMPOR INCIDIDUNT UT LABORE ET DOLORE MAGNA ALIQUA.',
            'mutasiSiswas' => $siswa->mutasiSiswas->map(function($mutasi) {
                return [
                    'id' => $mutasi->id,
                    'from_kelas' => $mutasi->fromKelas ? $mutasi->fromKelas->nama_kelas : '-',
                    'to_kelas' => $mutasi->toKelas ? $mutasi->toKelas->nama_kelas : '-',
                    'start_month' => \Carbon\Carbon::createFromFormat('Y-m', $mutasi->start_month)->isoFormat('MMMM YYYY'),
                    'spp_baru' => $mutasi->spp_baru,
                    'status' => $mutasi->status,
                    'created_at' => $mutasi->created_at->isoFormat('D MMMM YYYY, HH:mm'),
                ];
            }),
            'pageTitle' => 'Profil Saya',
        ]);
    }

    public function updateInformation(Request $request)
    {
        $user = $request->user();
        $siswa = $this->getActiveSiswa($user);

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

    /**
     * Memperbarui foto profil siswa.
     */
    public function updatePhoto(Request $request)
    {
        $user = $request->user();
        $siswa = $this->getActiveSiswa($user);

        $request->validate([
            'foto' => ['required', 'image', 'mimes:jpeg,png,jpg,webp', 'max:2048'], // Max 2MB
        ]);

        if ($request->hasFile('foto')) {
            // Hapus foto lama jika ada
            if ($siswa->foto && \Illuminate\Support\Facades\Storage::disk('public')->exists($siswa->foto)) {
                \Illuminate\Support\Facades\Storage::disk('public')->delete($siswa->foto);
            }

            // Simpan foto baru di folder fotos
            $path = $request->file('foto')->store('fotos', 'public');
            
            $siswa->forceFill([
                'foto' => $path,
            ])->save();
        }

        return back()->with([
            'message' => 'Foto profil berhasil diperbarui.',
            'type' => 'success'
        ]);
    }
}