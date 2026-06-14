<?php

namespace App\Http\Controllers;

use App\Models\BackupRun;
use App\Services\BackupService;
use Illuminate\Http\Request;

class BackupController extends Controller
{
    public function index()
    {
        $backups = BackupRun::latest()->paginate(20);

        return view('backups.index', compact('backups'));
    }

    public function store(Request $request, BackupService $backupService)
    {
        $validated = $request->validate([
            'backup_name' => ['nullable', 'string', 'max:120', 'regex:/^[A-Za-z0-9_.-]+$/'],
        ]);

        $run = $backupService->create(
            name: $validated['backup_name'] ?? null,
            createdBy: $request->user()->id
        );

        if ($run->status !== 'COMPLETED') {
            return back()->with('error', 'Backup failed: ' . $run->notes);
        }

        return back()->with('success', 'Backup created successfully.');
    }
}
