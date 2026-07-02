<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Validates the payload for updating an order's status.
 *
 * Validates: Requirements 7.2, 7.3
 */
class UpdateOrderStatusRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'status' => [
                'required',
                'string',
                'in:Diterima,Diproses,Dimasak,Selesai,Disajikan,Dibatalkan',
            ],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'status.required' => 'Status pesanan wajib diisi.',
            'status.in'       => 'Status pesanan tidak valid. Status yang diizinkan: Diterima, Diproses, Dimasak, Selesai, Disajikan, Dibatalkan.',
        ];
    }
}
