<?php

namespace App\Repositories;

use App\Models\Product;
use Illuminate\Database\Eloquent\Collection;

class ProductRepository extends BaseRepository
{
    public function __construct(Product $model)
    {
        parent::__construct($model);
    }

    public function getWithRelations(array $relations = ['vendor', 'category', 'brand'])
    {
        return $this->model->with($relations);
    }

    public function search(string $search)
    {
        return $this->model->where('title', 'like', "%{$search}%")
            ->orWhere('product_code', 'like', "%{$search}%");
    }

    public function getInHouseProducts()
    {
        return $this->model->whereNull('vendor_id');
    }

    public function getVendorProducts()
    {
        return $this->model->whereNotNull('vendor_id');
    }
}

