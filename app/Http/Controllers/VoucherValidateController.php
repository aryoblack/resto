<?php

namespace App\Http\Controllers;

use App\Services\PromoService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * VoucherValidateController — Public endpoint for validating voucher codes.
 *
 * Routes:
 *   POST /api/voucher/validate  → validate
 *
 * Validates: Requirements 12.3, 12.4, 12.5
 */
class VoucherValidateController extends Controller
{
    public function __construct(private readonly PromoService $promoService)
    {
    }

    /**
     * Validate a voucher code against a cart total.
     *
     * Request body:
     *   - code:       string (required)
     *   - cart_total: numeric (required, min:0)
     *
     * Response:
     *   - valid:           bool
     *   - discount_amount: float
     *   - message:         string
     *   - promo:           Promo|null (basic info when valid)
     *
     * Validates: Requirements 12.3, 12.4, 12.5
     */
    public function validate(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'code' => 'required|string|max:100',
            'cart_total' => [
                'required_without:total_amount',
                'numeric',
                'min:0',
            ],
            'total_amount' => [
                'required_without:cart_total',
                'numeric',
                'min:0',
            ],
        ]);

        $result = $this->promoService->validateVoucher(
            code: $validated['code'],
            cartTotal: (float) ($validated['cart_total'] ?? $validated['total_amount']),
        );

        $statusCode = $result['valid'] ? 200 : 422;

        return response()->json([
            'message' => $result['message'],
            'valid' => $result['valid'],
            'discount_amount' => $result['discount_amount'],
            'data'    => [
                'valid'           => $result['valid'],
                'discount_amount' => $result['discount_amount'],
                'promo'           => $result['promo'] ? [
                    'id'           => $result['promo']->id,
                    'name'         => $result['promo']->name,
                    'code'         => $result['promo']->code,
                    'type'         => $result['promo']->type,
                    'value'        => $result['promo']->value,
                    'min_purchase' => $result['promo']->min_purchase,
                    'max_discount' => $result['promo']->max_discount,
                ] : null,
            ],
        ], $statusCode);
    }
}
