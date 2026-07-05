<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Category;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

class CategoryController extends Controller
{
    public function index()
    {
        $categories = Category::with(['createdBy', 'updatedBy'])
            ->withCount(['products as products_count' => fn ($q) => $q->withTrashed()])
            ->latest()
            ->paginate(10);

        return view('admin.categories.index', compact('categories'));
    }

    public function create()
    {
        return view('admin.categories.create');
    }

    public function store(Request $request)
    {
        $validated = $this->validateCategory($request);

        Category::create([
            'name'       => $validated['name'],
            'created_by' => Auth::id(),
            'updated_by' => Auth::id(),
        ]);

        return redirect()->route('admin.categories.index')->with('success', 'Kategori berhasil dibuat.');
    }

    public function edit(Category $category)
    {
        $category->load(['createdBy', 'updatedBy']);

        return view('admin.categories.edit', compact('category'));
    }

    public function update(Request $request, Category $category)
    {
        $validated = $this->validateCategory($request, $category);

        $category->update([
            'name'       => $validated['name'],
            'updated_by' => Auth::id(),
        ]);

        return redirect()->route('admin.categories.index')->with('success', 'Kategori berhasil diubah.');
    }

    public function destroy(Category $category)
    {
        if ($category->hasProducts()) {
            return redirect()
                ->route('admin.categories.index')
                ->withErrors(['delete' => 'Kategori tidak bisa dihapus karena masih memiliki produk atau pernah digunakan oleh produk. Pindahkan produk ke kategori lain terlebih dahulu.']);
        }

        $category->update(['updated_by' => Auth::id()]);
        $category->delete();

        return redirect()->route('admin.categories.index')->with('success', 'Kategori berhasil dipindahkan ke trash.');
    }

    public function trash()
    {
        $categories = Category::onlyTrashed()
            ->with(['createdBy', 'updatedBy'])
            ->latest()
            ->paginate(10);

        return view('admin.categories.trash', compact('categories'));
    }

    public function restore(string $id)
    {
        $category = Category::withTrashed()->findOrFail($id);

        $category->restore();
        $category->update(['updated_by' => Auth::id()]);

        return redirect()->route('admin.categories.trash')->with('success', 'Kategori berhasil direstore.');
    }

    private function validateCategory(Request $request, ?Category $category = null): array
    {
        $request->merge(['name' => strtoupper(trim($request->name))]);

        $nameUnique = Rule::unique('categories', 'name')->whereNull('deleted_at');

        if ($category) {
            $nameUnique = $nameUnique->ignore($category->id);
        }

        return $request->validate([
            'name' => ['required', 'string', 'max:255', $nameUnique],
        ]);
    }
}