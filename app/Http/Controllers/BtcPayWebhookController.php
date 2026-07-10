<?php

namespace App\Http\Controllers;

use App\Models\PaymentEvent;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class BtcPayWebhookController extends Controller
{
    public function __invoke(Request $request): Response
    {
        $secret = (string) config('services.btc_pay.webhook_secret');

        if ($secret === '') {
            abort(Response::HTTP_SERVICE_UNAVAILABLE);
        }

        $expected = 'sha256='.hash_hmac('sha256', $request->getContent(), $secret);

        if (! hash_equals($expected, (string) $request->header('BTCPay-Sig'))) {
            abort(Response::HTTP_FORBIDDEN);
        }

        if ($request->input('storeId') !== config('services.btc_pay.store_id')) {
            abort(Response::HTTP_FORBIDDEN);
        }

        if ($request->input('type') === 'InvoiceSettled') {
            $invoiceId = (string) $request->input('invoiceId');

            if ($invoiceId !== '') {
                PaymentEvent::query()
                    ->where('btc_pay_invoice', $invoiceId)
                    ->where('paid', false)
                    ->update(['paid' => true]);
            }
        }

        return response()->noContent();
    }
}
