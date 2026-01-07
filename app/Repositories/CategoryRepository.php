<?php

namespace App\Repositories;

use App\Models\Category;

class CategoryRepository extends BaseRepository
{
    public function __construct(Category $model)
    {
        parent::__construct($model);
    }

    public function getParentCategories()
    {
        return $this->model->whereNull('parent_id')->orderBy('name')->get();
    }

    public function getSubCategories(int $parentId)
    {
        return $this->model->where('parent_id', $parentId)->orderBy('name')->get();
    }

    public function getWithProducts()
    {
        return $this->model->with('products')->orderBy('name')->get();
    }
}

