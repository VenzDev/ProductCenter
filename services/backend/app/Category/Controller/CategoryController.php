<?php

namespace App\Category\Controller;

use App\Category\Resource\CategoryResource;
use App\Http\Controllers\Controller;
use App\Models\Category;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class CategoryController extends Controller
{
    /**
     * List categories.
     *
     * The translatable name field is returned in the language requested via the
     * `Accept-Language` header (`en` or `pl`), falling back to `en` otherwise.
     * Pass `?include=translations` to additionally get every language for that
     * field as a `name_translations` map.
     */
    public function index(): AnonymousResourceCollection
    {
        return CategoryResource::collection(Category::query()->paginate());
    }

    /**
     * Retrieve a single category.
     *
     * The translatable name field is returned in the language requested via the
     * `Accept-Language` header (`en` or `pl`), falling back to `en` otherwise.
     * Pass `?include=translations` to additionally get every language for that
     * field as a `name_translations` map.
     */
    public function show(Category $category): CategoryResource
    {
        return new CategoryResource($category);
    }
}
