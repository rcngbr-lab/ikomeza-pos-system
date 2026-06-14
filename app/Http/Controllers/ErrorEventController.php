<?php

namespace App\Http\Controllers;

use App\Models\ErrorEvent;
use Illuminate\Http\Request;

class ErrorEventController extends Controller
{
    public function index(Request $request)
    {
        $events = ErrorEvent::query()
            ->when($request->filled('status'), fn ($query) => $query->where('status', strtoupper($request->status)))
            ->when($request->filled('severity'), fn ($query) => $query->where('severity', strtoupper($request->severity)))
            ->latest()
            ->paginate(25)
            ->withQueryString();

        return view('errors.index', compact('events'));
    }
}
