<?php

namespace App\Http\Controllers;

use App\Models\Category;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

use App\Http\Requests\StoreCategoryRequest;


class CategoryController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        abort_unless($request->user()->admin, 403);

        $categories = Category::query()
            ->orderBy('title')
            ->orderBy('id')
            ->get(['id', 'title']);

        return response()->json($categories);
    }

    public function selectable(): JsonResponse
    {
        $categories = Category::query()
            ->orderBy('title')
            ->orderBy('id')
            ->get(['id', 'title']);

        return response()->json($categories);
    }

    public function store(StoreCategoryRequest $request): JsonResponse
    {
        $category = new Category();

        $category->title = $request->validated('title');
        $category->save();

        return response()->json([
            'id' => $category->id,
            'title' => $category->title,
        ], 201);
    }

    public function destroy(
        Request $request,
        Category $category
    ): JsonResponse {
        abort_unless($request->user()?->admin, 403);

        if ($category->tickets()->exists()) {
            return response()->json([
                'message' => 'This category cannot be deleted because it is associated with tickets.',
            ], 409);
        }

        $category->delete();

        return response()->json([
            'message' => 'Category deleted successfully.',
        ]);
    }
}
