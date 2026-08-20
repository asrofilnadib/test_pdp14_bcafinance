<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;

class UpdatePengajuanRequest extends FormRequest
{
    public function authorize(): bool
    {
        return Auth::check() && Auth::user()->canCreatePengajuan();
    }

    public function rules(): array
    {
        return [
            'dealer_id' => ['nullable', 'exists:dealers,id'],
            'konsumen_nama' => ['required', 'string', 'max:150'],
            'konsumen_nik' => ['required', 'digits:16'],
            'konsumen_tgl_lahir' => ['nullable', 'date', 'before:today'],
            'status_perkawinan' => ['nullable', 'in:belum_menikah,menikah,cerai'],
            'data_pasangan' => ['nullable', 'string', 'max:150'],
            'merk_kendaraan' => ['nullable', 'string', 'max:80'],
            'model_kendaraan' => ['nullable', 'string', 'max:80'],
            'tipe_kendaraan' => ['nullable', 'string', 'max:80'],
            'warna_kendaraan' => ['nullable', 'string', 'max:40'],
            'harga_kendaraan' => ['nullable', 'numeric', 'min:0'],
            'asuransi' => ['nullable', 'string', 'max:80'],
            'down_payment' => ['nullable', 'numeric', 'min:0'],
            'lama_kredit' => ['nullable', 'integer', 'min:1', 'max:84'],
            'angsuran' => ['nullable', 'numeric', 'min:0'],
        ];
    }
}
