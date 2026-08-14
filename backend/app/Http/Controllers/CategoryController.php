<?php

namespace App\Http\Controllers;

use App\Http\Requests\BulkDeleteCategoryRequest;
use App\Http\Requests\StoreCategoryRequest;
use App\Http\Requests\UpdateCategoryRequest;
use App\Models\Category;
use App\Services\CategoryService;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class CategoryController extends Controller
{
    use ApiResponse;

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
                : 10,
            page: max(1, (int) $request->input('page', 1))
        );

        if ($request->ajax() || $request->header('X-Requested-With') === 'XMLHttpRequest') {
            return view('categories._table', [
                'categories' => $categories,
            ])->render();
        }

        $allCategories = $this->categoryService->getAllCategories();
        $tree = $this->categoryService->getTree();

        return view('categories.index', [
            'categories' => $categories,
            'allCategories' => $allCategories,
            'tree' => $tree,
        ]);
    }

    /**
     * Create category.
     */
    public function store(StoreCategoryRequest $request): JsonResponse
    {
        try {
            $category = $this->categoryService->create($request->validated());

            return $this->successResponse(
                data: $category->load('parent'),
                message: 'Category created successfully.',
                statusCode: 201
            );
        } catch (ValidationException $exception) {
            return $this->validationErrorResponse(
                exception: $exception,
                defaultMessage: 'Unable to create category.'
            );
        }
    }

    /**
     * Get category details for editing.
     */
    public function show(Category $category): JsonResponse
    {
        return $this->successResponse(
            data: $category->load('parent')
        );
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

            return $this->successResponse(
                data: $category,
                message: 'Category updated successfully.'
            );
        } catch (ValidationException $exception) {
            return $this->validationErrorResponse(
                exception: $exception,
                defaultMessage: 'Unable to update category.'
            );
        }
    }

    /**
     * Delete one category.
     */
    public function destroy(Category $category): JsonResponse
    {
        try {
            $this->categoryService->delete($category);

            return $this->successResponse(
                message: 'Category deleted successfully.'
            );
        } catch (ValidationException $exception) {
            return $this->validationErrorResponse(
                exception: $exception,
                defaultMessage: 'Unable to delete category.',
                errorKey: 'category'
            );
        }
    }

    /**
     * Delete multiple categories.
     */
    public function bulkDestroy(BulkDeleteCategoryRequest $request): JsonResponse
    {
        try {
            $this->categoryService->bulkDelete($request->validated('ids'));

            return $this->successResponse(
                message: 'Categories deleted successfully.'
            );
        } catch (ValidationException $exception) {
            return $this->validationErrorResponse(
                exception: $exception,
                defaultMessage: 'Unable to delete categories.',
                errorKey: 'ids'
            );
        }
    }

    /**
     * Get available parent categories.
     */
    public function parents(?Category $category = null): JsonResponse
    {
        $parents = $this->categoryService->getAvailableParents($category);

        return $this->successResponse(data: $parents);
    }

    /**
     * Get category tree.
     */
    public function tree(): JsonResponse
    {
        $tree = $this->categoryService->getTree();

        return $this->successResponse(data: $tree);
    }
}