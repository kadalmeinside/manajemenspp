<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Password;
use App\Models\Siswa;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class UpdateSiswaRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        $user = $this->user();
        if (!$user->can('edit_siswa')) {
            return false;
        }

        if ($user->hasRole('admin_kelas')) {
            $siswa = $this->route('siswa');
            $managedKelasIds = $user->managedClasses()->pluck('kelas.id_kelas')->toArray();
            if (!in_array($siswa->id_kelas, $managedKelasIds)) {
                return false;
            }
        }

        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $siswa = $this->route('siswa'); // Instance Siswa dari Route Model Binding
        Log::info('[UpdateSiswaRequest] Siswa ID: ' . ($siswa ? $siswa->id_siswa : 'Siswa not found in route'));
        Log::info('[UpdateSiswaRequest] Siswa Object: ', $siswa ? $siswa->toArray() : []); // Lihat seluruh data siswa

        $userId = null;
        if ($siswa && $siswa->user) { // Pastikan relasi user ada
            $userId = $siswa->user->id; // Ambil ID dari relasi user
            Log::info('[UpdateSiswaRequest] User ID from $siswa->user->id: ' . $userId);
        } else {
            Log::info('[UpdateSiswaRequest] Siswa or Siswa->user relation not found. $userId will be null.');
        }

        Log::info('[UpdateSiswaRequest] Submitted email_wali: ' . $this->input('email_wali'));

        return [
            'nis' => 'nullable|string|max:255',
            'nama_siswa' => 'required|string|max:255',
            'status_siswa' => 'required|string|in:Aktif,Non-Aktif,Lulus,Cuti',
            'id_kelas' => 'required|uuid|exists:kelas,id_kelas',
            'email_wali' => 'required|string|email|max:255|unique:users,email' . ($userId ? ',' . $userId : ''),
            'nomor_telepon_wali' => 'nullable|string|max:20',
            'tanggal_lahir' => 'required|date',
            'tanggal_bergabung' => 'required|date',
            'jumlah_spp_custom' => 'required|numeric|min:0',
            'admin_fee_custom' => 'nullable|numeric|min:0',
            'user_name' => 'required|string|max:255',
            'user_password' => ['nullable', 'confirmed', Password::min(8)->mixedCase()->numbers()->symbols()],
        ];
    }
    protected function prepareForValidation()
    {
        $this->merge([
            'nama_siswa' => $this->nama_siswa ? Str::title(strtolower(trim($this->nama_siswa))) : null,
            'user_name' => $this->user_name ? Str::title(strtolower(trim($this->user_name))) : null,
        ]);
    }
    
    public function messages(): array
    {
        return [
            'nis.max' => 'NIS maksimal 255 karakter.',
            'nama_siswa.required' => 'Nama lengkap siswa wajib diisi.',
            'nama_siswa.string' => 'Nama siswa harus berupa teks.',
            'nama_siswa.max' => 'Nama siswa maksimal 255 karakter.',
            'status_siswa.required' => 'Status siswa wajib diisi.',
            'status_siswa.in' => 'Status siswa tidak valid.',
            'id_kelas.required' => 'Kelas wajib dipilih.',
            'id_kelas.exists' => 'Kelas yang dipilih tidak valid.',
            'email_wali.required' => 'Email wali wajib diisi.',
            'email_wali.email' => 'Format email tidak valid.',
            'email_wali.unique' => 'Email ini sudah digunakan oleh user lain.',
            'nomor_telepon_wali.max' => 'Nomor WhatsApp maksimal 20 karakter.',
            'tanggal_lahir.required' => 'Tanggal lahir wajib diisi.',
            'tanggal_lahir.date' => 'Format tanggal lahir tidak valid.',
            'tanggal_bergabung.required' => 'Tanggal bergabung wajib diisi.',
            'tanggal_bergabung.date' => 'Format tanggal bergabung tidak valid.',
            'jumlah_spp_custom.required' => 'Jumlah SPP wajib diisi.',
            'jumlah_spp_custom.numeric' => 'Jumlah SPP harus berupa angka.',
            'jumlah_spp_custom.min' => 'Jumlah SPP tidak boleh kurang dari 0.',
            'admin_fee_custom.numeric' => 'Biaya admin harus berupa angka.',
            'admin_fee_custom.min' => 'Biaya admin tidak boleh kurang dari 0.',
            'user_name.required' => 'Nama wali wajib diisi.',
            'user_name.string' => 'Nama wali harus berupa teks.',
            'user_name.max' => 'Nama wali maksimal 255 karakter.',
            'user_password.confirmed' => 'Konfirmasi password tidak cocok.',
        ];
    }
}
