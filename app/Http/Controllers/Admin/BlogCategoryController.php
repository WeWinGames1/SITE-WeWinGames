<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\BlogCategory;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class BlogCategoryController extends Controller
{
    /**
     * Display a listing of the categories.
     */
    public function index(): JsonResponse
    {
        $categories = BlogCategory::ordered()->get();
            
        return response()->json($categories);
    }
    
    /**
     * Store a newly created category.
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'slug' => 'required|string|max:255|unique:blog_categories,slug',
            'description' => 'nullable|string|max:500',
        ]);
        
        $category = BlogCategory::create($validated);
        
        return response()->json([
            'message' => 'Category created successfully',
            'category' => $category->fresh(),
        ]);
    }
    
    /**
     * Update the specified category.
     */
    public function update(Request $request, BlogCategory $blogCategory): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'slug' => 'required|string|max:255|unique:blog_categories,slug,' . $blogCategory->id,
            'description' => 'nullable|string|max:500',
        ]);
        
        $blogCategory->update($validated);
        
        return response()->json([
            'message' => 'Category updated successfully',
            'category' => $blogCategory->fresh(),
        ]);
    }
    
    /**
     * Remove the specified category.
     */
    public function destroy(BlogCategory $blogCategory): JsonResponse
    {
        // Check if category has posts
        if ($blogCategory->posts_count > 0) {
            return response()->json([
                'message' => 'Cannot delete category with existing posts. Please reassign posts first.',
            ], 422);
        }
        
        $blogCategory->delete();
        
        return response()->json([
            'message' => 'Category deleted successfully',
        ]);
    }
    
    /**
     * Update category order.
     */
    public function updateOrder(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'categories' => 'required|array',
            'categories.*.id' => 'required|exists:blog_categories,id',
            'categories.*.order_column' => 'required|integer|min:0',
        ]);
        
        foreach ($validated['categories'] as $item) {
            BlogCategory::where('id', $item['id'])
                ->update(['order_column' => $item['order_column']]);
        }
        
        return response()->json([
            'message' => 'Category order updated successfully',
        ]);
    }
}
