<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Password;

class StoreSiswaRequest extends FormRequest
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
        return [
            // Data Siswa
            'nama_siswa' => 'required|string|max:255',
            'status_siswa' => 'required|string|in:Aktif,Non-Aktif,Lulus,Cuti',
            'id_kelas' => 'required|uuid|exists:kelas,id_kelas',
            'email_wali' => 'required|string|email|max:255|unique:users,email',
            'nomor_telepon_wali' => 'nullable|string|max:20',
            'tanggal_lahir' => 'required|date',
            'tanggal_bergabung' => 'required|date',
            'jumlah_spp_custom' => 'nullable|numeric|min:0',
            'admin_fee_custom' => 'nullable|numeric|min:0',

            // Data untuk Akun User baru (siswa/wali)
            'user_name' => 'required|string|max:255',
            'user_password' => ['required', 'confirmed', Password::min(8)->mixedCase()->numbers()->symbols()],
        ];
    }

    public function messages(): array
    {
        return [
            'email_wali.unique' => 'Email ini sudah terdaftar sebagai akun user.',
            'id_kelas.required' => 'Kelas wajib dipilih.',
        ];
    }
}
