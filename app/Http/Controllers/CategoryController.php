<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Category;
use App\Services\CategoryCatalogService;
use App\Services\DepartmentAccessService;
use App\Services\DepartmentCatalogService;
use Illuminate\Validation\Rule;
use Illuminate\Support\Str;

class CategoryController extends Controller
{
    public function index(Request $request, DepartmentAccessService $departmentAccess)
    {
        app(CategoryCatalogService::class)->ensureDefaults();

        $selectedDepartmentId = $departmentAccess->selectedDepartmentId(
            $request->user(),
            $request->integer('department_id') ?: null
        );

        $departments = $departmentAccess->visibleDepartments($request->user());

        $query = Category::with('department')
            ->withCount('products')
            ->when($selectedDepartmentId, fn ($builder) => $builder->where('department_id', $selectedDepartmentId))
            ->orderBy('sort_order')
            ->orderBy('name');

        if (request('search')) {
            $search = request('search');

            $query->where(function ($builder) use ($search) {
                $builder->where('name', 'like', "%{$search}%")
                    ->orWhere('code', 'like', "%{$search}%");
            });
        }

        return view('categories.index', [
            'categories' => $query->paginate(20)->withQueryString(),
            'totalCategories' => Category::when($selectedDepartmentId, fn ($builder) => $builder->where('department_id', $selectedDepartmentId))->count(),
            'activeCategories' => Category::when($selectedDepartmentId, fn ($builder) => $builder->where('department_id', $selectedDepartmentId))->where('active', true)->count(),
            'assignedProducts' => Category::withCount('products')->when($selectedDepartmentId, fn ($builder) => $builder->where('department_id', $selectedDepartmentId))->get()->sum('products_count'),
            'departments' => $departments,
            'selectedDepartmentId' => $selectedDepartmentId,
        ]);
    }

    public function create()
    {
        app(DepartmentCatalogService::class)->ensureDefaults();

        return view('categories.create', [
            'departments' => app(DepartmentAccessService::class)->visibleDepartments(auth()->user()),
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255', 'unique:categories,name'],
            'code' => ['nullable', 'string', 'max:50', 'unique:categories,code'],
            'department_id' => ['required', 'exists:departments,id'],
            'description' => ['nullable', 'string', 'max:500'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
            'active' => ['nullable', 'boolean'],
        ]);

        app(DepartmentAccessService::class)->authorize(
            $request->user(),
            (int) $validated['department_id']
        );

        Category::create([
            'name' => $validated['name'],
            'code' => ($validated['code'] ?? null) ?: $this->uniqueCode($validated['name']),
            'department_id' => $validated['department_id'],
            'description' => $validated['description'] ?? null,
            'sort_order' => $validated['sort_order'] ?? 0,
            'active' => $request->boolean('active'),
        ]);

        return redirect()
            ->route('categories.index')
            ->with('success', 'Category created successfully.');
    }

    public function show(Category $category)
    {
        return redirect()->route('categories.edit', $category);
    }

    public function edit(Category $category)
    {
        app(DepartmentAccessService::class)->authorize(
            auth()->user(),
            $category->department_id
        );

        app(DepartmentCatalogService::class)->ensureDefaults();

        return view('categories.edit', [
            'category' => $category,
            'departments' => app(DepartmentAccessService::class)->visibleDepartments(auth()->user()),
        ]);
    }

    public function update(Request $request, Category $category)
    {
        app(DepartmentAccessService::class)->authorize(
            $request->user(),
            $category->department_id
        );

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255', Rule::unique('categories', 'name')->ignore($category->id)],
            'code' => ['nullable', 'string', 'max:50', Rule::unique('categories', 'code')->ignore($category->id)],
            'department_id' => ['required', 'exists:departments,id'],
            'description' => ['nullable', 'string', 'max:500'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
            'active' => ['nullable', 'boolean'],
        ]);

        app(DepartmentAccessService::class)->authorize(
            $request->user(),
            (int) $validated['department_id']
        );

        $category->update([
            'name' => $validated['name'],
            'code' => ($validated['code'] ?? null) ?: $this->uniqueCode($validated['name'], $category->id),
            'department_id' => $validated['department_id'],
            'description' => $validated['description'] ?? null,
            'sort_order' => $validated['sort_order'] ?? 0,
            'active' => $request->boolean('active'),
        ]);

        return redirect()
            ->route('categories.index')
            ->with('success', 'Category updated successfully.');
    }

    public function destroy(Category $category)
    {
        app(DepartmentAccessService::class)->authorize(
            auth()->user(),
            $category->department_id
        );

        if ($category->products()->exists()) {
            return redirect()
                ->route('categories.index')
                ->with('error', 'This category has products. Move those products before deleting it.');
        }

        $category->delete();

        return redirect()
            ->route('categories.index')
            ->with('success', 'Category deleted successfully.');
    }

    private function uniqueCode(string $name, ?int $ignoreId = null): string
    {
        $base = Str::upper(Str::slug($name, '_')) ?: 'CATEGORY';
        $code = $base;
        $count = 2;

        while (
            Category::where('code', $code)
                ->when($ignoreId, fn ($query) => $query->where('id', '!=', $ignoreId))
                ->exists()
        ) {
            $code = "{$base}_{$count}";
            $count++;
        }

        return $code;
    }
}
