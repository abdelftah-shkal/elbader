<?php

namespace App\Http\Controllers;

use App\Models\Category;
use Illuminate\Http\Request;
use App\Services\CategoryService;
use Illuminate\Support\Facades\DB;

class CategoryController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    protected $categoryService;

    public function __construct(CategoryService $categoryService)
    {
        $this->categoryService = $categoryService;
    }
    public function index(Request $request)
    {
        $query = Category::with('parent');

        if ($request->filled('category_id')) {
            $category = Category::findOrFail(
                $request->category_id
            );

            $descendantIds = $this->categoryService
                ->getDescendantIds($category);

            $query->whereIn('id', $descendantIds);
        }

        if ($request->filled('search')) {
            $query->where(
                'name',
                'like',
                '%' . $request->search . '%'
            );
        }

        $categories = $query
            ->orderBy('name')
            ->paginate(10)
            ->withQueryString();

        $allCategories = Category::orderBy('name')->get();

        return view('categories.index', [
            'categories' => $categories,
            'allCategories' => $allCategories,
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        //
    }

    /**
     * Display the specified resource.
     */
    public function show(Category $category)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Category $category)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Category $category)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Category $category)
    {
        //
    }
}
