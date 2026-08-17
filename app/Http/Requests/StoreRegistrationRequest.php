<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreRegistrationRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'nama_siswa'           => 'required|string|max:255',
            'tanggal_lahir'        => 'required|date',
            'id_kelas'             => 'required|uuid|exists:kelas,id_kelas',
            'user_name'            => 'required|string|max:255',
            'email_wali'           => 'required|string|email|max:255',
            'nomor_telepon_wali'   => 'required|string|max:20',
            'terms'                => 'accepted',
            'legal_document_id'    => 'required|exists:legal_documents,id',
            'kode_promo'           => 'nullable|string|exists:promos,kode_promo',
        ];
    }

    /**
     * Get the error messages for the defined validation rules.
     */
    public function messages(): array
    {
        return [
            'nama_siswa.required'         => 'Nama siswa wajib diisi.',
            'nama_siswa.string'           => 'Nama siswa harus berupa teks.',
            'nama_siswa.max'              => 'Nama siswa maksimal 255 karakter.',
            'tanggal_lahir.required'      => 'Tanggal lahir wajib diisi.',
            'tanggal_lahir.date'          => 'Format tanggal lahir tidak valid.',
            'id_kelas.required'           => 'Pilihan cabang atau kelas wajib diisi.',
            'id_kelas.exists'             => 'Cabang atau kelas yang dipilih tidak valid.',
            'user_name.required'          => 'Nama lengkap wali wajib diisi.',
            'user_name.string'            => 'Nama wali harus berupa teks.',
            'user_name.max'               => 'Nama wali maksimal 255 karakter.',
            'email_wali.required'         => 'Alamat email wali wajib diisi.',
            'email_wali.email'            => 'Format alamat email tidak valid.',
            'nomor_telepon_wali.required' => 'Nomor WhatsApp wali wajib diisi.',
            'terms.accepted'              => 'Anda harus menyetujui syarat dan ketentuan yang berlaku.',
            'legal_document_id.required'  => 'Dokumen persetujuan wajib diisi.',
            'kode_promo.exists'           => 'Kode promo tidak ditemukan atau tidak valid.',
        ];
    }
}
