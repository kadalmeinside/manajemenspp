<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreStudentLeaveRequest extends FormRequest
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
            'id_siswa' => 'required|exists:siswa,id_siswa',
            'months'   => 'required|array|min:1',
            'months.*' => 'required|integer|min:1|max:12',
            'year'     => 'required|integer|min:' . date('Y'),
            'reason'   => 'required|string|max:500',
        ];
    }
}
