<?php

namespace App\Http\Controllers\Shop;

use App\Http\Controllers\Controller;
use App\Models\Transaction;
use App\Services\Orders\PaymentService;
use App\Services\Payment\PaymentManager;
use Illuminate\Http\Request;

class PaymentController extends Controller
{
    public function callback(
        Request $request,
        string $gateway,
        PaymentManager $gateways,
        PaymentService $payments,
    ) {
        $transaction = $gateways->driver($gateway)->verify($request);

        $order = $transaction->order;

        if ($transaction->status === Transaction::SUCCESS) {
            $payments->markPaid($order, $transaction);
        }

        return redirect()->route('checkout.result', $order);
    }
}
