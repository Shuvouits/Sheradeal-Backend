<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AbandonedCheckoutController extends Controller
{
    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'checkout_token' => ['required', 'uuid'],
            'consent' => ['required', 'accepted'],
            'customer_name' => ['nullable', 'string', 'max:150'],
            'customer_phone' => [
                'required',
                'regex:/^01[3-9]\d{8}$/',
            ],
            'customer_email' => ['nullable', 'email', 'max:255'],
            'items' => ['required', 'array', 'min:1', 'max:50'],
            'items.*.product_id' => [
                'required', 'integer', 'exists:products,id',
            ],
            'items.*.quantity' => [
                'required', 'integer', 'min:1', 'max:100',
            ],
        ]);


DB::transaction(function () use ($data) {
    $query = DB::table('abandoned_checkouts')
        ->where('checkout_token', $data['checkout_token'])
        ->lockForUpdate();

    $existing = $query->first();

    if ($existing && $existing->status !== 'open') {
        return;
    }

    $values = [
        'customer_name' => $data['customer_name'] ?? null,
        'customer_phone' => $data['customer_phone'],
        'customer_email' => $data['customer_email'] ?? null,
        'items' => json_encode($data['items']),
        'updated_at' => now(),
    ];

    if ($existing) {
        DB::table('abandoned_checkouts')
            ->where('id', $existing->id)
            ->update($values);
    } else {
        DB::table('abandoned_checkouts')->insert([
            ...$values,
            'checkout_token' => $data['checkout_token'],
            'status' => 'open',
            'created_at' => now(),
        ]);
    }
});


        return response()->json([
            'success' => true,
            'message' => 'Checkout progress saved.',
        ]);
    }
}
