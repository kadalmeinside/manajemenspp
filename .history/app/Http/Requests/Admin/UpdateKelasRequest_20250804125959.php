<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class UpdateKelasRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()->can('manage_kelas');
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $kelasId = $this->route('kela')->id_kelas;
        return [
            'nama_kelas' => 'required|string|max:100|unique:kelas,nama_kelas,' . $kelasId . ',id_kelas',
            'deskripsi' => 'nullable|string|max:1000',
            'biaya_spp_default' => 'nullable|numeric|min:0',
            'biaya_pendaftaran' => 'nullable|numeric|min:0', 
        ];
    }

    public function messages(): array
    {
        return [
            'nama_kelas.unique' => 'Nama kelas sudah ada.',
        ];
    }
}
