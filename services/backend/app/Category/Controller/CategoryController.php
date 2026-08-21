<?php

namespace App\Category\Controller;

use App\Category\Resource\CategoryResource;
use App\Http\Controllers\Controller;
use App\Models\Category;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class CategoryController extends Controller
{
    /**
     * List categories as a tree.
     *
     * Only root categories are returned at the top level; each includes its
     * subcategories nested under `children` (empty array if it has none — the
     * category structure is capped at two levels, so children never have
     * children of their own).
     *
     * The translatable name field is returned in the language requested via the
     * `Accept-Language` header (`en` or `pl`), falling back to `en` otherwise.
     * Pass `?include=translations` to additionally get every language for that
     * field as a `name_translations` map.
     */
    public function index(): AnonymousResourceCollection
    {
        return CategoryResource::collection(
            Category::query()->isRoot()->with('children')->ordered()->get()
        );
    }

    /**
     * Retrieve a single category, with its subcategories nested under `children`
     * (empty array if it has none).
     *
     * The translatable name field is returned in the language requested via the
     * `Accept-Language` header (`en` or `pl`), falling back to `en` otherwise.
     * Pass `?include=translations` to additionally get every language for that
     * field as a `name_translations` map.
     */
    public function show(Category $category): CategoryResource
    {
        return new CategoryResource($category->loadMissing('children'));
    }
}
