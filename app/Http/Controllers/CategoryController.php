<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Category;
use App\Services\CategoryCatalogService;
use Illuminate\Validation\Rule;
use Illuminate\Support\Str;

class CategoryController extends Controller
{
    public function index()
    {
        app(CategoryCatalogService::class)->ensureDefaults();

        $query = Category::withCount('products')
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
            'totalCategories' => Category::count(),
            'activeCategories' => Category::where('active', true)->count(),
            'assignedProducts' => Category::withCount('products')->get()->sum('products_count'),
        ]);
    }

    public function create()
    {
        return view('categories.create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255', 'unique:categories,name'],
            'code' => ['nullable', 'string', 'max:50', 'unique:categories,code'],
            'description' => ['nullable', 'string', 'max:500'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
            'active' => ['nullable', 'boolean'],
        ]);

        Category::create([
            'name' => $validated['name'],
            'code' => ($validated['code'] ?? null) ?: $this->uniqueCode($validated['name']),
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
        return view('categories.edit', compact('category'));
    }

    public function update(Request $request, Category $category)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255', Rule::unique('categories', 'name')->ignore($category->id)],
            'code' => ['nullable', 'string', 'max:50', Rule::unique('categories', 'code')->ignore($category->id)],
            'description' => ['nullable', 'string', 'max:500'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
            'active' => ['nullable', 'boolean'],
        ]);

        $category->update([
            'name' => $validated['name'],
            'code' => ($validated['code'] ?? null) ?: $this->uniqueCode($validated['name'], $category->id),
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
