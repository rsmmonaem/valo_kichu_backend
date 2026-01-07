<?php

namespace App\Repositories;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Collection;

abstract class BaseRepository implements RepositoryInterface
{
    protected $model;

    public function __construct(Model $model)
    {
        $this->model = $model;
    }

    public function all(): Collection
    {
        return $this->model->all();
    }

    public function find(int $id): ?Model
    {
        return $this->model->find($id);
    }

    public function findOrFail(int $id): Model
    {
        return $this->model->findOrFail($id);
    }

    public function create(array $data): Model
    {
        return $this->model->create($data);
    }

    public function update(int $id, array $data): bool
    {
        $model = $this->findOrFail($id);
        return $model->update($data);
    }

    public function delete(int $id): bool
    {
        $model = $this->findOrFail($id);
        return $model->delete();
    }

    public function paginate(int $perPage = 15)
    {
        return $this->model->paginate($perPage);
    }

    public function where($column, $value = null)
    {
        // If array is passed, apply multiple where conditions
        if (is_array($column)) {
            $query = $this->model;
            foreach ($column as $key => $val) {
                if (is_array($val)) {
                    // Handle complex conditions like ['column', 'operator', 'value']
                    if (count($val) === 3) {
                        $query = $query->where($val[0], $val[1], $val[2]);
                    } elseif (count($val) === 2) {
                        $query = $query->where($val[0], $val[1]);
                    }
                } else {
                    // Simple key-value pair
                    $query = $query->where($key, $val);
                }
            }
            return $query;
        }
        
        // Single where condition
        return $this->model->where($column, $value);
    }

    public function with(array $relations)
    {
        return $this->model->with($relations);
    }

    public function getModel()
    {
        return $this->model;
    }
}

