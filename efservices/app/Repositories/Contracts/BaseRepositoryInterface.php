<?php

namespace App\Repositories\Contracts;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Pagination\LengthAwarePaginator;

/**
 * Base Repository Interface
 * 
 * Define los métodos comunes que todos los repositorios deben implementar.
 */
interface BaseRepositoryInterface
{
    /**
     * Obtener todos los registros
     *
     * @param array $columns
     * @return Collection
     */
    public function all(array $columns = ['*']): Collection;

    /**
     * Obtener registros paginados
     *
     * @param int $perPage
     * @param array $columns
     * @return LengthAwarePaginator
     */
    public function paginate(int $perPage = 15, array $columns = ['*']): LengthAwarePaginator;

    /**
     * Encontrar un registro por ID
     *
     * @param int $id
     * @param array $columns
     * @return Model|null
     */
    public function find(int $id, array $columns = ['*']): ?Model;

    /**
     * Encontrar un registro por ID o fallar
     *
     * @param int $id
     * @param array $columns
     * @return Model
     * @throws \Illuminate\Database\Eloquent\ModelNotFoundException
     */
    public function findOrFail(int $id, array $columns = ['*']): Model;

    /**
     * Encontrar registros por criterio
     *
     * @param array $criteria
     * @param array $columns
     * @return Collection
     */
    public function findBy(array $criteria, array $columns = ['*']): Collection;

    /**
     * Encontrar un registro por criterio
     *
     * @param array $criteria
     * @param array $columns
     * @return Model|null
     */
    public function findOneBy(array $criteria, array $columns = ['*']): ?Model;

    /**
     * Crear un nuevo registro
     *
     * @param array $data
     * @return Model
     */
    public function create(array $data): Model;

    /**
     * Actualizar un registro
     *
     * @param int $id
     * @param array $data
     * @return bool
     */
    public function update(int $id, array $data): bool;

    /**
     * Eliminar un registro
     *
     * @param int $id
     * @return bool
     */
    public function delete(int $id): bool;

    /**
     * Contar registros
     *
     * @param array $criteria
     * @return int
     */
    public function count(array $criteria = []): int;

    /**
     * Verificar si existe un registro
     *
     * @param array $criteria
     * @return bool
     */
    public function exists(array $criteria): bool;

    /**
     * Cargar relaciones
     *
     * @param array $relations
     * @return self
     */
    public function with(array $relations): self;

    /**
     * Ordenar resultados
     *
     * @param string $column
     * @param string $direction
     * @return self
     */
    public function orderBy(string $column, string $direction = 'asc'): self;
}
