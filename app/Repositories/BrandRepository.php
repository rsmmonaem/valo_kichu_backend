<?php

namespace App\Repositories;

use App\Models\Brand;

class BrandRepository extends BaseRepository
{
    public function __construct(Brand $model)
    {
        parent::__construct($model);
    }

    public function getWithProducts()
    {
        return $this->model->with('products')->orderBy('name')->get();
    }

    public function search(string $search)
    {
        return $this->model->where('name', 'like', "%{$search}%");
    }
}

