<?php

namespace App\Repositories;

use App\Repositories\Contracts\BaseRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Pagination\LengthAwarePaginator;

/**
 * Base Repository
 * 
 * Implementación base de repositorio con métodos comunes.
 */
abstract class BaseRepository implements BaseRepositoryInterface
{
    /**
     * @var Model
     */
    protected Model $model;

    /**
     * @var array
     */
    protected array $with = [];

    /**
     * @var array
     */
    protected array $orderBy = [];

    /**
     * Constructor
     */
    public function __construct()
    {
        $this->model = $this->makeModel();
    }

    /**
     * Crear instancia del modelo
     *
     * @return Model
     */
    abstract protected function makeModel(): Model;

    /**
     * Obtener todos los registros
     */
    public function all(array $columns = ['*']): Collection
    {
        $query = $this->model->newQuery();

        if (!empty($this->with)) {
            $query->with($this->with);
        }

        if (!empty($this->orderBy)) {
            foreach ($this->orderBy as $column => $direction) {
                $query->orderBy($column, $direction);
            }
        }

        return $query->get($columns);
    }

    /**
     * Obtener registros paginados
     */
    public function paginate(int $perPage = 15, array $columns = ['*']): LengthAwarePaginator
    {
        $query = $this->model->newQuery();

        if (!empty($this->with)) {
            $query->with($this->with);
        }

        if (!empty($this->orderBy)) {
            foreach ($this->orderBy as $column => $direction) {
                $query->orderBy($column, $direction);
            }
        }

        return $query->paginate($perPage, $columns);
    }

    /**
     * Encontrar un registro por ID
     */
    public function find(int $id, array $columns = ['*']): ?Model
    {
        $query = $this->model->newQuery();

        if (!empty($this->with)) {
            $query->with($this->with);
        }

        return $query->find($id, $columns);
    }

    /**
     * Encontrar un registro por ID o fallar
     */
    public function findOrFail(int $id, array $columns = ['*']): Model
    {
        $query = $this->model->newQuery();

        if (!empty($this->with)) {
            $query->with($this->with);
        }

        return $query->findOrFail($id, $columns);
    }

    /**
     * Encontrar registros por criterio
     */
    public function findBy(array $criteria, array $columns = ['*']): Collection
    {
        $query = $this->model->newQuery();

        if (!empty($this->with)) {
            $query->with($this->with);
        }

        foreach ($criteria as $key => $value) {
            if (is_array($value)) {
                $query->whereIn($key, $value);
            } else {
                $query->where($key, $value);
            }
        }

        if (!empty($this->orderBy)) {
            foreach ($this->orderBy as $column => $direction) {
                $query->orderBy($column, $direction);
            }
        }

        return $query->get($columns);
    }

    /**
     * Encontrar un registro por criterio
     */
    public function findOneBy(array $criteria, array $columns = ['*']): ?Model
    {
        $query = $this->model->newQuery();

        if (!empty($this->with)) {
            $query->with($this->with);
        }

        foreach ($criteria as $key => $value) {
            $query->where($key, $value);
        }

        return $query->first($columns);
    }

    /**
     * Crear un nuevo registro
     */
    public function create(array $data): Model
    {
        return $this->model->create($data);
    }

    /**
     * Actualizar un registro
     */
    public function update(int $id, array $data): bool
    {
        $model = $this->find($id);

        if (!$model) {
            return false;
        }

        return $model->update($data);
    }

    /**
     * Eliminar un registro
     */
    public function delete(int $id): bool
    {
        $model = $this->find($id);

        if (!$model) {
            return false;
        }

        return $model->delete();
    }

    /**
     * Contar registros
     */
    public function count(array $criteria = []): int
    {
        $query = $this->model->newQuery();

        foreach ($criteria as $key => $value) {
            if (is_array($value)) {
                $query->whereIn($key, $value);
            } else {
                $query->where($key, $value);
            }
        }

        return $query->count();
    }

    /**
     * Verificar si existe un registro
     */
    public function exists(array $criteria): bool
    {
        $query = $this->model->newQuery();

        foreach ($criteria as $key => $value) {
            $query->where($key, $value);
        }

        return $query->exists();
    }

    /**
     * Cargar relaciones
     */
    public function with(array $relations): self
    {
        $this->with = $relations;
        return $this;
    }

    /**
     * Ordenar resultados
     */
    public function orderBy(string $column, string $direction = 'asc'): self
    {
        $this->orderBy[$column] = $direction;
        return $this;
    }

    /**
     * Resetear configuración del query
     */
    protected function resetQuery(): void
    {
        $this->with = [];
        $this->orderBy = [];
    }
}
