<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Category;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

class CategoryController extends Controller
{
    /**
     * Display a listing of categories.
     */
    public function index()
    {
        $categories = Category::with(['createdBy', 'updatedBy'])
            ->latest()
            ->paginate(10);

        return view('admin.categories.index', compact('categories'));
    }

    /**
     * Show the form for creating a new category.
     */
    public function create()
    {
        return view('admin.categories.create');
    }

    /**
     * Store a newly created category.
     */
    public function store(Request $request)
    {
        $request->merge([
            'name' => strtoupper(trim($request->name)),
        ]);

        $validated = $request->validate([
            'name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('categories', 'name')->whereNull('deleted_at'),
            ],
        ]);

        Category::create([
            'name' => $validated['name'],
            'created_by' => Auth::id(),
            'updated_by' => Auth::id(),
        ]);

        return redirect()
            ->route('admin.categories.index')
            ->with('success', 'Kategori berhasil dibuat.');
    }

    /**
     * Show the form for editing the category.
     */
    public function edit(Category $category)
    {
        $category->load(['createdBy', 'updatedBy']);

        return view('admin.categories.edit', compact('category'));
    }

    /**
     * Update the specified category.
     */
    public function update(Request $request, Category $category)
    {
        $request->merge([
            'name' => strtoupper(trim($request->name)),
        ]);

        $validated = $request->validate([
            'name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('categories', 'name')
                    ->whereNull('deleted_at')
                    ->ignore($category->id),
            ],
        ]);

        $category->update([
            'name' => $validated['name'],
            'updated_by' => Auth::id(),
        ]);

        return redirect()
            ->route('admin.categories.index')
            ->with('success', 'Kategori berhasil diubah.');
    }

    /**
     * Soft delete the specified category.
     */
    public function destroy(Category $category)
    {
        $category->delete();

        return redirect()
            ->route('admin.categories.index')
            ->with('success', 'Kategori berhasil dihapus.');
    }

    /**
     * Display trashed categories.
     */
    public function trash()
    {
        $categories = Category::onlyTrashed()
            ->with(['createdBy', 'updatedBy'])
            ->latest()
            ->paginate(10);

        return view('admin.categories.trash', compact('categories'));
    }

    /**
     * Restore the specified trashed category.
     */
    public function restore(string $id)
    {
        $category = Category::withTrashed()->findOrFail($id);

        $category->restore();

        $category->update(['updated_by' => Auth::id()]);

        return redirect()
            ->route('admin.categories.trash')
            ->with('success', 'Kategori berhasil direstore.');
    }
}