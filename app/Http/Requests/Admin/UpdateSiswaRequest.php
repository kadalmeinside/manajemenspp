<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Password;
use App\Models\Siswa;
use Illuminate\Support\Facades\Log;

class UpdateSiswaRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()->can('manage_siswa');
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
            'nama_siswa' => 'required|string|max:255',
            'status_siswa' => 'required|string|in:Aktif,Non-Aktif,Lulus,Cuti',
            'id_kelas' => 'required|uuid|exists:kelas,id_kelas',
            'email_wali' => 'required|string|email|max:255|unique:users,email' . ($userId ? ',' . $userId : ''),
            'nomor_telepon_wali' => 'nullable|string|max:20',
            'tanggal_lahir' => 'required|date',
            'tanggal_bergabung' => 'required|date',
            'jumlah_spp_custom' => 'nullable|numeric|min:0',
            'admin_fee_custom' => 'nullable|numeric|min:0',
            'user_name' => 'required|string|max:255',
            'user_password' => ['nullable', 'confirmed', Password::min(8)->mixedCase()->numbers()->symbols()],
        ];
    }
    
    public function messages(): array
    {
        return [
            'email_wali.unique' => 'Email ini sudah digunakan oleh user lain.',
            'id_kelas.required' => 'Kelas wajib dipilih.',
        ];
    }
}
