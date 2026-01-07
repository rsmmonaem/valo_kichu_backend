<?php

namespace App\Services;

use App\Repositories\BrandRepository;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class BrandService
{
    protected $repository;

    public function __construct(BrandRepository $repository)
    {
        $this->repository = $repository;
    }

    public function getAll(Request $request)
    {
        $query = $this->repository->getModel()->with('products');

        if ($request->has('search') && !empty($request->search)) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('slug', 'like', "%{$search}%")
                  ->orWhere('description', 'like', "%{$search}%");
            });
        }

        return $query->orderBy('name')->get();
    }

    public function find(int $id)
    {
        return $this->repository->findOrFail($id);
    }

    public function create(array $data)
    {
        if (isset($data['image']) && $data['image']->isValid()) {
            $data['image'] = $data['image']->store('brands', 'public');
        }

        // Auto-generate slug if not provided
        if (empty($data['slug']) && !empty($data['name'])) {
            $data['slug'] = \Illuminate\Support\Str::slug($data['name']);
        }

        // Set default status
        if (!isset($data['status'])) {
            $data['status'] = true;
        }

        return $this->repository->create($data);
    }

    public function update(int $id, array $data)
    {
        $brand = $this->repository->findOrFail($id);

        if (isset($data['image']) && $data['image']->isValid()) {
            // Delete old image
            if ($brand->image && Storage::disk('public')->exists($brand->image)) {
                Storage::disk('public')->delete($brand->image);
            }
            $data['image'] = $data['image']->store('brands', 'public');
        }

        // Auto-generate slug if not provided
        if (empty($data['slug']) && !empty($data['name'])) {
            $data['slug'] = \Illuminate\Support\Str::slug($data['name']);
        }

        return $this->repository->update($id, $data);
    }

    public function delete(int $id)
    {
        $brand = $this->repository->findOrFail($id);
        
        // Delete image if exists
        if ($brand->image && Storage::disk('public')->exists($brand->image)) {
            Storage::disk('public')->delete($brand->image);
        }

        return $this->repository->delete($id);
    }
}

