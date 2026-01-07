<?php

namespace App\Services;

use App\Repositories\CategoryRepository;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class CategoryService
{
    protected $repository;

    public function __construct(CategoryRepository $repository)
    {
        $this->repository = $repository;
    }

    public function getAll(Request $request)
    {
        $query = $this->repository->getModel()->with(['parent', 'subcategories', 'products']);

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
            $data['image'] = $data['image']->store('categories', 'public');
        }

        if (isset($data['banner']) && $data['banner']->isValid()) {
            $data['banner'] = $data['banner']->store('categories/banners', 'public');
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
        $category = $this->repository->findOrFail($id);

        if (isset($data['image']) && $data['image']->isValid()) {
            // Delete old image
            if ($category->image && Storage::disk('public')->exists($category->image)) {
                Storage::disk('public')->delete($category->image);
            }
            $data['image'] = $data['image']->store('categories', 'public');
        }

        if (isset($data['banner']) && $data['banner']->isValid()) {
            // Delete old banner
            if ($category->banner && Storage::disk('public')->exists($category->banner)) {
                Storage::disk('public')->delete($category->banner);
            }
            $data['banner'] = $data['banner']->store('categories/banners', 'public');
        }

        // Auto-generate slug if not provided
        if (empty($data['slug']) && !empty($data['name'])) {
            $data['slug'] = \Illuminate\Support\Str::slug($data['name']);
        }

        return $this->repository->update($id, $data);
    }

    public function delete(int $id)
    {
        $category = $this->repository->findOrFail($id);
        
        // Delete image if exists
        if ($category->image && Storage::disk('public')->exists($category->image)) {
            Storage::disk('public')->delete($category->image);
        }

        // Delete banner if exists
        if ($category->banner && Storage::disk('public')->exists($category->banner)) {
            Storage::disk('public')->delete($category->banner);
        }

        return $this->repository->delete($id);
    }

    public function getParentCategories()
    {
        return $this->repository->getParentCategories();
    }

    public function getSubCategories(int $parentId)
    {
        return $this->repository->getSubCategories($parentId);
    }
}

