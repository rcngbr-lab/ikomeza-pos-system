<?php

namespace App\Http\Controllers;

use App\Models\RestaurantTable;
use Illuminate\Http\Request;

class TableController extends Controller
{
    public function index()
    {
        $tables = RestaurantTable::orderBy('section')->orderBy('name')->get();

        return view('tables.index', compact('tables'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'section' => ['nullable', 'string', 'max:80'],
            'seats' => ['required', 'integer', 'min:1', 'max:50'],
        ]);

        RestaurantTable::create([
            'table_code' => 'TBL-' . now()->format('His') . '-' . random_int(100, 999),
            'name' => $validated['name'],
            'section' => $validated['section'] ?? null,
            'seats' => $validated['seats'],
            'status' => RestaurantTable::STATUS_AVAILABLE,
        ]);

        return back()->with('success', 'Table created.');
    }

    public function updateStatus(Request $request, RestaurantTable $table)
    {
        $validated = $request->validate([
            'status' => ['required', 'in:AVAILABLE,OCCUPIED,RESERVED,OUT_OF_SERVICE'],
        ]);

        if (
            !$request->user()->hasOperationalRole('ADMIN', 'ADMINISTRATOR', 'MANAGER')
            && !in_array($validated['status'], ['AVAILABLE', 'OCCUPIED'], true)
        ) {
            abort(403);
        }

        $table->update(['status' => $validated['status']]);

        return back()->with('success', 'Table status updated.');
    }
}
