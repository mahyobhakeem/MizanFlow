<?php

namespace App\Http\Controllers;

use App\Http\Requests\CategoryRequest;
use App\Models\Category;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class CategoryController extends Controller
{
    public function index(Request $request): View
    {
        Gate::authorize('viewAny', Category::class);

        $categories = Category::query()
            ->with('parent')
            ->withCount('products', 'children')
            ->search($request->input('search'))
            ->when($request->filled('status'), fn ($q) => $q->where('is_active', $request->boolean('status')))
            ->orderBy('id', 'desc')
            ->paginate(15)
            ->withQueryString();

        return view('categories.index', compact('categories'));
    }

    public function create(): View
    {
        Gate::authorize('create', Category::class);

        $parentCategories = Category::active()->whereNull('parent_id')->orderBy('name')->get();

        return view('categories.create', compact('parentCategories'));
    }

    public function store(CategoryRequest $request): RedirectResponse
    {
        Gate::authorize('create', Category::class);

        Category::create($request->validated());

        return redirect()->route('categories.index')
            ->with('success', 'تم إنشاء التصنيف بنجاح.');
    }

    public function edit(Category $category): View
    {
        Gate::authorize('update', $category);

        $parentCategories = Category::active()
            ->whereNull('parent_id')
            ->where('id', '!=', $category->id)
            ->orderBy('name')
            ->get();

        return view('categories.edit', compact('category', 'parentCategories'));
    }

    public function update(CategoryRequest $request, Category $category): RedirectResponse
    {
        Gate::authorize('update', $category);

        $category->update($request->validated());

        return redirect()->route('categories.index')
            ->with('success', 'تم تحديث التصنيف بنجاح.');
    }

    public function destroy(Category $category): RedirectResponse
    {
        Gate::authorize('delete', $category);

        if (! $category->canBeDeleted()) {
            return back()->with('error', 'لا يمكن حذف هذا التصنيف لاحتوائه على منتجات أو تصنيفات فرعية مرتبطة به.');
        }

        $category->delete();

        return redirect()->route('categories.index')
            ->with('success', 'تم حذف التصنيف بنجاح.');
    }
}
