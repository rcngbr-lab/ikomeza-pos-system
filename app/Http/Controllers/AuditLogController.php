<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\Branch;
use App\Models\Department;
use App\Models\StockRequisition;
use App\Models\User;
use App\Services\DepartmentAccessService;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Symfony\Component\HttpFoundation\Response;

class AuditLogController extends Controller
{
    private const MODULES = [
        'Sales',
        'Inventory',
        'Products',
        'Users',
        'Roles',
        'Permissions',
        'Shifts',
        'Requisitions',
        'Purchases',
        'Suppliers',
        'Refunds',
        'Security',
        'System',
    ];

    private const ACTIONS = [
        'CREATED',
        'UPDATED',
        'DELETED',
        'APPROVED',
        'REJECTED',
        'LOGIN_SUCCESS',
        'LOGIN_FAILED',
        'LOGOUT',
        'SALE_COMPLETED',
        'REFUND_CREATED',
        'STOCK_IN',
        'STOCK_OUT',
        'STOCK_ADJUSTED',
        'REQUISITION_SUBMITTED',
        'REQUISITION_APPROVED',
        'REQUISITION_REJECTED',
        'PURCHASE_RECEIVED',
        'SHIFT_OPENED',
        'SHIFT_CLOSED',
        'PRICE_CHANGED',
    ];

    private const SEVERITIES = [
        'INFO',
        'WARNING',
        'CRITICAL',
        'SECURITY',
    ];

    public function index(Request $request)
    {
        $this->authorizeAccess($request);

        $filteredQuery = $this->filteredQuery($request);

        $perPage = $this->perPage($request);

        $logs = (clone $filteredQuery)
            ->with(['user', 'department', 'branch'])
            ->latest()
            ->paginate($perPage)
            ->withQueryString();

        return view('audit_logs.index', [
            'logs' => $logs,
            'summary' => $this->summary($request, $filteredQuery),
            'users' => $this->visibleUsers($request),
            'departments' => app(DepartmentAccessService::class)->visibleDepartments($request->user()),
            'branches' => Branch::orderBy('name')->get(['id', 'name', 'code']),
            'modules' => $this->visibleModules($request->user()),
            'actions' => self::ACTIONS,
            'severities' => self::SEVERITIES,
            'roles' => $this->roles(),
            'periods' => $this->periods(),
            'perPageOptions' => [20, 50, 100],
            'canExport' => $this->canExport($request->user()),
        ]);
    }

    public function show(Request $request, AuditLog $auditLog)
    {
        $this->authorizeAccess($request);
        $this->authorizeRecord($request, $auditLog);

        $auditLog->load(['user', 'department', 'branch']);

        return view('audit_logs.show', compact('auditLog'));
    }

    public function print(Request $request)
    {
        $this->authorizeExport($request);

        $logs = $this->filteredQuery($request)
            ->with(['user', 'department', 'branch'])
            ->latest()
            ->limit(1000)
            ->get();

        return view('audit_logs.print', [
            'logs' => $logs,
            'filters' => $request->query(),
        ]);
    }

    public function export(Request $request, string $format): Response
    {
        $this->authorizeExport($request);

        $logs = $this->filteredQuery($request)
            ->with(['user', 'department', 'branch'])
            ->latest()
            ->limit(5000)
            ->get();

        $format = strtolower($format);

        return match ($format) {
            'csv' => $this->csvResponse($logs),
            'excel', 'xls' => $this->excelResponse($logs),
            'pdf' => $this->pdfResponse($logs, $request),
            default => abort(404),
        };
    }

    private function filteredQuery(Request $request): Builder
    {
        $query = $this->visibleQuery($request);

        $this->applyDateFilter($query, $request);
        $this->applySearchFilters($query, $request);

        return $query;
    }

    private function visibleQuery(Request $request): Builder
    {
        $user = $request->user();
        $query = AuditLog::query();

        if ($user->hasOperationalRole('ADMIN', 'ADMINISTRATOR')) {
            return $query;
        }

        if ($user->hasOperationalRole('MANAGER')) {
            return $query->whereNotIn('module', ['Security', 'Users', 'Roles', 'Permissions']);
        }

        if ($user->hasOperationalRole('CASHIER', 'WAITER', 'SERVER')) {
            return $query
                ->where('user_id', $user->id)
                ->whereIn('module', ['Sales', 'Refunds', 'Shifts']);
        }

        if ($user->hasOperationalRole('STORE_KEEPER')) {
            return $query
                ->whereIn('module', ['Inventory', 'Products', 'Requisitions', 'Purchases', 'Suppliers'])
                ->when($user->department_id, function ($builder) use ($user) {
                    $builder->where(function ($departmentQuery) use ($user) {
                        $departmentQuery->where('department_id', $user->department_id)
                            ->orWhere('user_id', $user->id);
                    });
                });
        }

        if ($user->hasOperationalRole('KITCHEN_MANAGER', 'KITCHEN_CHIEF', 'BAR_MANAGER', 'BAR_CHIEF', 'BARTENDER')) {
            $allowed = app(DepartmentAccessService::class)->allowedDepartmentIds($user);

            abort_if(empty($allowed), 403);

            return $query
                ->whereIn('module', ['Sales', 'Inventory', 'Products', 'Requisitions', 'Refunds'])
                ->whereIn('department_id', $allowed);
        }

        abort(403);
    }

    private function applySearchFilters(Builder $query, Request $request): void
    {
        if ($request->filled('search')) {
            $search = trim((string) $request->search);

            $query->where(function ($builder) use ($search) {
                $builder->where('action', 'like', '%' . $search . '%')
                    ->orWhere('event', 'like', '%' . $search . '%')
                    ->orWhere('module', 'like', '%' . $search . '%')
                    ->orWhere('model', 'like', '%' . $search . '%')
                    ->orWhere('reference', 'like', '%' . $search . '%')
                    ->orWhere('description', 'like', '%' . $search . '%')
                    ->orWhere('ip_address', 'like', '%' . $search . '%')
                    ->orWhereHas('user', function ($userQuery) use ($search) {
                        $userQuery->where('name', 'like', '%' . $search . '%')
                            ->orWhere('email', 'like', '%' . $search . '%');
                    });
            });
        }

        if ($request->filled('reference')) {
            $reference = trim((string) $request->reference);

            $query->where(function ($builder) use ($reference) {
                $builder->where('reference', 'like', '%' . $reference . '%')
                    ->orWhere('description', 'like', '%' . $reference . '%');
            });
        }

        if ($request->filled('user_id')) {
            $query->where('user_id', $request->integer('user_id'));
        }

        if ($request->filled('user_search')) {
            $search = trim((string) $request->user_search);

            $query->whereHas('user', function ($userQuery) use ($search) {
                $userQuery->where('name', 'like', '%' . $search . '%')
                    ->orWhere('email', 'like', '%' . $search . '%');
            });
        }

        if ($request->filled('role')) {
            $role = strtoupper((string) $request->role);

            $query->where(function ($builder) use ($role) {
                $builder->whereRaw('UPPER(role_name) = ?', [$role])
                    ->orWhereHas('user', fn ($userQuery) => $userQuery->whereRaw('UPPER(role) = ?', [$role]));
            });
        }

        if ($request->filled('department_id')) {
            $department = $request->input('department_id');

            if ($department === 'global') {
                $query->whereNull('department_id');
            } else {
                app(DepartmentAccessService::class)->authorize($request->user(), (int) $department);
                $query->where('department_id', (int) $department);
            }
        }

        if ($request->filled('module')) {
            $module = str((string) $request->module)->replace('_', ' ')->title()->toString();

            $query->where(function ($builder) use ($module) {
                $builder->where('module', $module)
                    ->orWhere('model', $module);
            });
        }

        if ($request->filled('action')) {
            $action = strtoupper((string) $request->action);

            $query->where(function ($builder) use ($action) {
                $builder->where('action', $action)
                    ->orWhere('event', $action);
            });
        }

        if ($request->filled('severity')) {
            $query->where('severity', strtoupper((string) $request->severity));
        }
    }

    private function applyDateFilter(Builder $query, Request $request): void
    {
        $period = $request->input('date_filter')
            ?: $request->input('period')
            ?: $request->input('filter');

        $period = match ($period) {
            'daily' => 'today',
            'weekly' => 'this_week',
            'monthly' => 'this_month',
            'yearly' => 'this_year',
            default => $period ?: 'all',
        };

        match ($period) {
            'today' => $query->whereDate('created_at', today()),
            'yesterday' => $query->whereDate('created_at', today()->subDay()),
            'this_week' => $query->whereBetween('created_at', [now()->startOfWeek(), now()->endOfWeek()]),
            'last_week' => $query->whereBetween('created_at', [now()->subWeek()->startOfWeek(), now()->subWeek()->endOfWeek()]),
            'this_month' => $query->whereBetween('created_at', [now()->startOfMonth(), now()->endOfMonth()]),
            'last_month' => $query->whereBetween('created_at', [now()->subMonthNoOverflow()->startOfMonth(), now()->subMonthNoOverflow()->endOfMonth()]),
            'this_year' => $query->whereBetween('created_at', [now()->startOfYear(), now()->endOfYear()]),
            default => null,
        };

        if ($request->filled('start_date')) {
            $query->where('created_at', '>=', Carbon::parse($request->start_date)->startOfDay());
        }

        if ($request->filled('end_date')) {
            $query->where('created_at', '<=', Carbon::parse($request->end_date)->endOfDay());
        }
    }

    private function summary(Request $request, Builder $filteredQuery): array
    {
        $visibleQuery = $this->visibleQuery($request);

        return [
            'total' => (clone $filteredQuery)->count(),
            'today' => (clone $visibleQuery)->whereDate('created_at', today())->count(),
            'security' => (clone $visibleQuery)
                ->where(function ($query) {
                    $query->where('severity', 'SECURITY')
                        ->orWhere('module', 'Security');
                })
                ->count(),
            'critical' => (clone $filteredQuery)->where('severity', 'CRITICAL')->count(),
            'stock' => (clone $filteredQuery)
                ->where(function ($query) {
                    $query->where('module', 'Inventory')
                        ->orWhere('event_type', 'STOCK')
                        ->orWhere('action', 'like', 'STOCK_%');
                })
                ->count(),
            'financial' => (clone $filteredQuery)
                ->where(function ($query) {
                    $query->whereNotNull('amount')
                        ->orWhereIn('module', ['Sales', 'Refunds', 'Purchases']);
                })
                ->count(),
            'pending_approvals' => $this->pendingApprovals($request),
        ];
    }

    private function pendingApprovals(Request $request): int
    {
        $user = $request->user();
        $allowedDepartments = app(DepartmentAccessService::class)->allowedDepartmentIds($user);

        if (!$user->hasOperationalRole('ADMIN', 'ADMINISTRATOR', 'MANAGER', 'STORE_KEEPER', 'KITCHEN_MANAGER', 'KITCHEN_CHIEF', 'BAR_MANAGER', 'BAR_CHIEF', 'BARTENDER')) {
            return 0;
        }

        return StockRequisition::query()
            ->where('status', StockRequisition::STATUS_PENDING)
            ->when($allowedDepartments !== null, fn ($query) => $query->whereIn('department_id', $allowedDepartments))
            ->when($user->hasOperationalRole('STORE_KEEPER') && $user->department_id, fn ($query) => $query->where('department_id', $user->department_id))
            ->count();
    }

    private function visibleUsers(Request $request)
    {
        $user = $request->user();

        return User::query()
            ->when($user->hasOperationalRole('CASHIER', 'WAITER', 'SERVER'), fn ($query) => $query->whereKey($user->id))
            ->when(
                $user->hasOperationalRole('KITCHEN_MANAGER', 'KITCHEN_CHIEF', 'BAR_MANAGER', 'BAR_CHIEF', 'BARTENDER', 'STORE_KEEPER') && $user->department_id,
                fn ($query) => $query->where(function ($userQuery) use ($user) {
                    $userQuery->where('department_id', $user->department_id)
                        ->orWhere('id', $user->id);
                })
            )
            ->orderBy('name')
            ->get(['id', 'name', 'email', 'role']);
    }

    private function authorizeAccess(Request $request): void
    {
        abort_unless($request->user()?->hasOperationalRole(
            'ADMIN',
            'ADMINISTRATOR',
            'MANAGER',
            'STORE_KEEPER',
            'KITCHEN_MANAGER',
            'KITCHEN_CHIEF',
            'BAR_MANAGER',
            'BAR_CHIEF',
            'BARTENDER',
            'CASHIER',
            'WAITER',
            'SERVER'
        ), 403);
    }

    private function authorizeExport(Request $request): void
    {
        $this->authorizeAccess($request);
        abort_unless($this->canExport($request->user()), 403);
    }

    private function authorizeRecord(Request $request, AuditLog $auditLog): void
    {
        abort_unless(
            $this->visibleQuery($request)->whereKey($auditLog->id)->exists(),
            403
        );
    }

    private function canExport($user): bool
    {
        return $user->hasOperationalRole('ADMIN', 'ADMINISTRATOR', 'MANAGER');
    }

    private function perPage(Request $request): int
    {
        $perPage = (int) $request->input('per_page', 20);

        return in_array($perPage, [20, 50, 100], true) ? $perPage : 20;
    }

    private function roles(): array
    {
        return [
            'ADMIN' => 'Admin / CEO',
            'MANAGER' => 'Manager',
            'CASHIER' => 'Cashier',
            'WAITER' => 'Waiter',
            'KITCHEN_MANAGER' => 'Kitchen Chief',
            'BAR_MANAGER' => 'Bar Chief',
            'STORE_KEEPER' => 'Store Keeper',
        ];
    }

    private function visibleModules($user): array
    {
        if ($user->hasOperationalRole('ADMIN', 'ADMINISTRATOR')) {
            return self::MODULES;
        }

        if ($user->hasOperationalRole('MANAGER')) {
            return ['Sales', 'Inventory', 'Products', 'Shifts', 'Requisitions', 'Purchases', 'Suppliers', 'Refunds'];
        }

        if ($user->hasOperationalRole('CASHIER', 'WAITER', 'SERVER')) {
            return ['Sales', 'Refunds', 'Shifts'];
        }

        if ($user->hasOperationalRole('STORE_KEEPER')) {
            return ['Inventory', 'Products', 'Requisitions', 'Purchases', 'Suppliers'];
        }

        return ['Sales', 'Inventory', 'Products', 'Requisitions', 'Refunds'];
    }

    private function periods(): array
    {
        return [
            'all' => 'All Time',
            'today' => 'Today',
            'yesterday' => 'Yesterday',
            'this_week' => 'This Week',
            'last_week' => 'Last Week',
            'this_month' => 'This Month',
            'last_month' => 'Last Month',
            'this_year' => 'This Year',
            'custom' => 'Custom Date Range',
        ];
    }

    private function csvResponse(Collection $logs): Response
    {
        $csv = $this->csvLines($logs)->implode("\n");

        return response($csv, 200, [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="audit-logs-' . now()->format('Ymd-His') . '.csv"',
        ]);
    }

    private function excelResponse(Collection $logs): Response
    {
        $rows = $logs->map(fn (AuditLog $log) => $this->exportRow($log));
        $html = view('audit_logs.export-excel', compact('rows'))->render();

        return response($html, 200, [
            'Content-Type' => 'application/vnd.ms-excel; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="audit-logs-' . now()->format('Ymd-His') . '.xls"',
        ]);
    }

    private function pdfResponse(Collection $logs, Request $request): Response
    {
        $lines = [
            'IKOMEZA POS AUDIT LOG REPORT',
            'Generated: ' . now()->format('Y-m-d H:i'),
            'Filters: ' . json_encode($request->query()),
            '',
            'ID | Date | User | Role | Action | Module | Ref | Severity | Description',
        ];

        foreach ($logs as $log) {
            $line = implode(' | ', [
                $log->id,
                optional($log->created_at)->format('Y-m-d H:i'),
                $log->user?->name ?? 'Unknown',
                $log->role_name ?: '-',
                $log->displayAction(),
                $log->displayModule(),
                $log->displayReference(),
                $log->severity ?: 'INFO',
                $log->description ?: '-',
            ]);

            foreach (str_split($this->plain($line), 130) as $part) {
                $lines[] = $part;
            }
        }

        return response($this->simplePdf($lines), 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'attachment; filename="audit-logs-' . now()->format('Ymd-His') . '.pdf"',
        ]);
    }

    private function csvLines(Collection $logs): Collection
    {
        $header = array_keys($this->exportRow($logs->first() ?: new AuditLog()));

        return collect([$this->csvRow($header)])
            ->merge($logs->map(fn (AuditLog $log) => $this->csvRow($this->exportRow($log))));
    }

    private function exportRow(AuditLog $log): array
    {
        return [
            'ID' => $log->id,
            'Date' => optional($log->created_at)->format('Y-m-d H:i:s'),
            'User' => $log->user?->name ?? 'Unknown',
            'Role' => $log->role_name ?: '-',
            'Action' => $log->displayAction(),
            'Module' => $log->displayModule(),
            'Department' => $log->department?->name ?? 'Global',
            'Branch' => $log->branch?->name ?? '-',
            'Reference' => $log->displayReference(),
            'Severity' => $log->severity ?: 'INFO',
            'Amount' => $log->amount,
            'Stock Before' => $log->quantity_before,
            'Stock Changed' => $log->quantity_changed,
            'Stock After' => $log->quantity_after,
            'IP Address' => $log->ip_address,
            'Device' => $log->device,
            'Description' => $log->description,
        ];
    }

    private function csvRow(array $values): string
    {
        return collect($values)
            ->map(fn ($value) => '"' . str_replace('"', '""', (string) $value) . '"')
            ->implode(',');
    }

    private function simplePdf(array $lines): string
    {
        $pages = array_chunk($lines, 42);
        $objects = [
            1 => '<< /Type /Catalog /Pages 2 0 R >>',
            3 => '<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica >>',
        ];
        $kids = [];
        $next = 4;

        foreach ($pages as $pageLines) {
            $pageId = $next++;
            $contentId = $next++;
            $kids[] = $pageId . ' 0 R';
            $content = $this->pdfText($pageLines);
            $objects[$pageId] = '<< /Type /Page /Parent 2 0 R /MediaBox [0 0 842 595] /Resources << /Font << /F1 3 0 R >> >> /Contents ' . $contentId . ' 0 R >>';
            $objects[$contentId] = '<< /Length ' . strlen($content) . " >>\nstream\n" . $content . "\nendstream";
        }

        $objects[2] = '<< /Type /Pages /Kids [' . implode(' ', $kids) . '] /Count ' . count($kids) . ' >>';
        ksort($objects);

        $pdf = "%PDF-1.4\n";
        $offsets = [0];

        foreach ($objects as $id => $body) {
            $offsets[$id] = strlen($pdf);
            $pdf .= $id . " 0 obj\n" . $body . "\nendobj\n";
        }

        $xref = strlen($pdf);
        $pdf .= "xref\n0 " . (count($objects) + 1) . "\n0000000000 65535 f \n";

        foreach (array_keys($objects) as $id) {
            $pdf .= str_pad((string) $offsets[$id], 10, '0', STR_PAD_LEFT) . " 00000 n \n";
        }

        return $pdf . "trailer\n<< /Size " . (count($objects) + 1) . " /Root 1 0 R >>\nstartxref\n" . $xref . "\n%%EOF";
    }

    private function pdfText(array $lines): string
    {
        $content = "BT\n/F1 9 Tf\n36 560 Td\n";

        foreach ($lines as $line) {
            $content .= '(' . $this->pdfEscape($line) . ") Tj\n0 -12 Td\n";
        }

        return $content . 'ET';
    }

    private function pdfEscape(string $text): string
    {
        return str_replace(['\\', '(', ')'], ['\\\\', '\\(', '\\)'], $this->plain($text));
    }

    private function plain(string $text): string
    {
        return trim(preg_replace('/[^\x20-\x7E]/', ' ', $text));
    }
}
