<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Validates the payload for creating a new order from the customer's cart.
 *
 * Validates: Requirements 5.5, 5.6
 */
class CreateOrderRequest extends FormRequest
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
            'table_id'              => ['nullable', 'integer', 'exists:table,id'],
            'order_type'            => ['required', 'in:dine_in,delivery'],
            'payment_method'        => ['nullable', 'in:cash,qris,card'],
            'discount_amount'       => ['nullable', 'numeric', 'min:0'],
            'notes'                 => ['nullable', 'string', 'max:500'],
            'voucher_code'          => ['nullable', 'string', 'max:100'],
            'items'                 => ['required', 'array', 'min:1'],
            'items.*.menu_id'       => ['required', 'integer', 'exists:menu,id'],
            'items.*.quantity'      => ['required', 'integer', 'min:1'],
            'items.*.variant_id'    => ['nullable', 'integer', 'exists:variant,id'],
            'items.*.variant_selected' => ['nullable', 'string', 'max:255'],
            'items.*.note'          => ['nullable', 'string', 'max:500'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'items.required'            => 'Keranjang tidak boleh kosong.',
            'items.min'                 => 'Minimal satu item harus ada di keranjang.',
            'items.*.menu_id.required'  => 'ID menu wajib diisi.',
            'items.*.menu_id.exists'    => 'Menu tidak ditemukan.',
            'items.*.quantity.required' => 'Jumlah item wajib diisi.',
            'items.*.quantity.min'      => 'Jumlah item minimal 1.',
        ];
    }
}
