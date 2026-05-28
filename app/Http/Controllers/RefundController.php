<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Refund;

class RefundController extends Controller
{
    public function index()
    {
        $refunds = Refund::with([
            'sale',
            'user'
        ])
        ->latest()
        ->get();

        return view(
            'refunds.index',
            compact('refunds')
        );
    }
}