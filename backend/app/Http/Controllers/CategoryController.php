<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreCategoryRequest;
use App\Http\Requests\UpdateCategoryRequest;
use App\Http\Requests\BulkDeleteCategoryRequest;
use App\Models\Category;
use App\Services\CategoryService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Illuminate\Validation\ValidationException;

class CategoryController extends Controller
{
    public function __construct(
        protected CategoryService $categoryService
    ) {
    }

    /**
     * Display categories page.
     */
    public function index(Request $request): View|\Illuminate\Http\Response|string
    {
        $categories = $this->categoryService->getPaginatedCategories(
            search: $request->input('search'),
            categoryId: $request->filled('category_id')
                ? (int) $request->input('category_id')
                : null,
            perPage: $request->filled('per_page')
                ? (int) $request->input('per_page')
                : 10
        );

        if ($request->ajax() || $request->header('X-Requested-With') === 'XMLHttpRequest') {
            return view('categories._table', [
                'categories' => $categories,
            ])->render();
        }

        $allCategories = $this->categoryService
            ->getAllCategories();

        $tree = $this->categoryService
            ->getTree();

        return view('categories.index', [
            'categories' => $categories,
            'allCategories' => $allCategories,
            'tree' => $tree,
        ]);
    }

    /**
     * Create category.
     */
    public function store(
        StoreCategoryRequest $request
    ): JsonResponse {
        try {
            $category = $this->categoryService->create(
                $request->validated()
            );

            return response()->json([
                'success' => true,
                'message' => 'Category created successfully.',
                'data' => $category->load('parent'),
            ], 201);

        } catch (ValidationException $exception) {
            return response()->json([
                'success' => false,
                'message' => 'Unable to create category.',
                'errors' => $exception->errors(),
            ], 422);
        }
    }

    /**
     * Get category details for editing.
     */
    public function show(Category $category): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data' => $category->load('parent'),
        ]);
    }

    /**
     * Update category.
     */
    public function update(
        UpdateCategoryRequest $request,
        Category $category
    ): JsonResponse {
        try {
            $category = $this->categoryService->update(
                $category,
                $request->validated()
            );

            return response()->json([
                'success' => true,
                'message' => 'Category updated successfully.',
                'data' => $category,
            ]);

        } catch (ValidationException $exception) {
            return response()->json([
                'success' => false,
                'message' => 'Unable to update category.',
                'errors' => $exception->errors(),
            ], 422);
        }
    }

    /**
     * Delete one category.
     */
    public function destroy(
        Category $category
    ): JsonResponse {
        try {
            $this->categoryService->delete($category);

            return response()->json([
                'success' => true,
                'message' => 'Category deleted successfully.',
            ]);

        } catch (ValidationException $exception) {
            return response()->json([
                'success' => false,
                'message' => $exception->errors()['category'][0]
                    ?? 'Unable to delete category.',
                'errors' => $exception->errors(),
            ], 422);
        }
    }

    /**
     * Delete multiple categories.
     */
    public function bulkDestroy(
        BulkDeleteCategoryRequest $request
    ): JsonResponse {
        try {
            $this->categoryService->bulkDelete(
                $request->validated('ids')
            );

            return response()->json([
                'success' => true,
                'message' => 'Categories deleted successfully.',
            ]);

        } catch (ValidationException $exception) {
            return response()->json([
                'success' => false,
                'message' => $exception->errors()['ids'][0]
                    ?? 'Unable to delete categories.',
                'errors' => $exception->errors(),
            ], 422);
        }
    }

    /**
     * Get available parent categories.
     */
    public function parents(
        ?Category $category = null
    ): JsonResponse {
        $parents = $this->categoryService
            ->getAvailableParents($category);

        return response()->json([
            'success' => true,
            'data' => $parents,
        ]);
    }

    /**
     * Get category tree.
     */
    public function tree(): JsonResponse
    {
        $tree = $this->categoryService->getTree();

        return response()->json([
            'success' => true,
            'data' => $tree,
        ]);
    }
}