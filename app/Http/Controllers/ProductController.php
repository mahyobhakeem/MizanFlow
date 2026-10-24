<?php

namespace App\Http\Controllers;

use App\Http\Requests\ProductRequest;
use App\Models\Category;
use App\Models\Product;
use App\Models\Unit;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class ProductController extends Controller
{
    public function index(Request $request): View
    {
        Gate::authorize('viewAny', Product::class);

        $products = Product::query()
            ->with(['category', 'unit'])
            ->search($request->input('search'))
            ->when($request->filled('category_id'), fn ($q) => $q->where('category_id', $request->input('category_id')))
            ->when($request->filled('unit_id'), fn ($q) => $q->where('unit_id', $request->input('unit_id')))
            ->when($request->filled('status'), fn ($q) => $q->where('is_active', $request->boolean('status')))
            ->orderBy('id', 'desc')
            ->paginate(15)
            ->withQueryString();

        $categories = Category::active()->orderBy('name')->get();
        $units = Unit::active()->orderBy('name')->get();

        return view('products.index', compact('products', 'categories', 'units'));
    }

    public function create(): View
    {
        Gate::authorize('create', Product::class);

        $categories = Category::active()->orderBy('name')->get();
        $units = Unit::active()->orderBy('name')->get();

        return view('products.create', compact('categories', 'units'));
    }

    public function store(ProductRequest $request): RedirectResponse
    {
        Gate::authorize('create', Product::class);

        Product::create($request->validated());

        return redirect()->route('products.index')
            ->with('success', 'تم إنشاء المنتج بنجاح.');
    }

    public function edit(Product $product): View
    {
        Gate::authorize('update', $product);

        $categories = Category::active()->orderBy('name')->get();
        $units = Unit::active()->orderBy('name')->get();

        return view('products.edit', compact('product', 'categories', 'units'));
    }

    public function update(ProductRequest $request, Product $product): RedirectResponse
    {
        Gate::authorize('update', $product);

        $product->update($request->validated());

        return redirect()->route('products.index')
            ->with('success', 'تم تحديث بيانات المنتج بنجاح.');
    }

    public function destroy(Product $product): RedirectResponse
    {
        Gate::authorize('delete', $product);

        if (! $product->canBeDeleted()) {
            return back()->with('error', 'لا يمكن حذف هذا المنتج لوجود حركات مخزنية أو فواتير مسجلة عليه.');
        }

        $product->delete();

        return redirect()->route('products.index')
            ->with('success', 'تم حذف المنتج بنجاح.');
    }
}
