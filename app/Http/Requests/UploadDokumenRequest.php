<?php

namespace App\Http\Requests;

use App\Models\DokumenPengajuan;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

class UploadDokumenRequest extends FormRequest
{
    public function authorize(): bool
    {
        return Auth::check();
    }

    public function rules(): array
    {
        $allowed = array_merge(DokumenPengajuan::TIPE_AWAL, DokumenPengajuan::TIPE_TTD);

        return [
            'tipe' => ['required', Rule::in($allowed)],
            'file' => ['required', 'file', 'mimes:jpg,jpeg,png,pdf', 'max:5120'],
        ];
    }

    public function messages(): array
    {
        return [
            'file.mimes' => 'Dokumen harus berupa JPG, PNG, atau PDF.',
            'file.max' => 'Ukuran dokumen maksimal 5 MB.',
        ];
    }
}
