<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\EinundzwanzigPleb;
use Illuminate\Http\Request;

class GetPaidMembers extends Controller
{
    public function __invoke($year, Request $request)
    {
        $paidMembers = EinundzwanzigPleb::query()
            ->whereHas('paymentEvents', function ($query) use ($year) {
                $query->where('year', $year)
                    ->where('paid', true);
            })
            ->select('id', 'npub', 'pubkey', 'nip05_handle')
            ->get();

        return response()->json($paidMembers);
    }
}
