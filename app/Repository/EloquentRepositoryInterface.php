<?php

namespace App\Repository;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Collection;

/**
 * Interface EloquentRepositoryInterface
 * @package App\Repository
 */
interface EloquentRepositoryInterface
{

    /**
     * Get all records
     * 
     * @return Collection
     */
    public function all(): Collection;

    /**
     * Create a record
     * 
     * @param array     $attributes Associative array with attributes values for the record
     * @return Model    Created model
     */
    public function create(array $attributes): Model;

    /**
     * Inserts one or several records
     * 
     * @param array $data  Associative array with attributes values for the record
     *                     Could be a matrix representing several records values 
     * @return void
     */
    public function insert(array $data): void;

    /**
     * Search for and return a record. Throw an exception if record not found
     * 
     * @param mixed ID of the record to be returned
     * @return Model
     * 
     * @throws \Illuminate\Database\Eloquent\ModelNotFoundException
     */
    public function find(mixed $id): Model;

    /**
     * Search for and return a record. Return null if record not found
     * 
     * @param mixed $id   ID of the record to be returned
     * @return Model|null
     */
    public function findOrNull(mixed $id): ?Model;

    /**
     * Get the first record that match filters criteria
     * 
     * @param array $filters    Array with elements defining a filter [field name, [operator], value]
     *                          Could be a matrix representing several filters criteria
     * @return Model|null 
     * 
     */
    public function findByFields(array $filters): ?Model;

    /**
     * Update a record
     * 
     * @param array $attributes   Associative array with values of attibutes to be updated
     * @param mixed $id           ID of the record to be updated
     * @return Model
     */
    public function update(array $attributes, mixed $id): Model;

    /**
     * Update or create a record. Get a set of filters attributes and a set of values for atributes to be updated
     * 
     * @param array $filters: Associative array with pairs (attribute => value) used as filters to obtain record to be updated
     * @param array $attributes: Associative array with pairs (attribute => value) used as values to be updated
     * @return Model
     */
    public function updateOrCreate(array $filters, array $attributes): Model;

    /**
     * Delete a single or set of records 
     * 
     * @param mixed $id(s) A single or set of IDs of records to be deleted
     * @return int Amount of deleted records
     */
    public function delete($ids): int;

    /**
     * Insert new records or update the existing ones.
     *
     * @param  array  $values
     * @param  array|string  $uniqueBy
     * @param  array|null  $update
     * @return int
     */
    public function upsert(array $values, $uniqueBy, $update = null);
}
