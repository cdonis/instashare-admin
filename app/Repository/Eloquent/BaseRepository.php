<?php

namespace App\Repository\Eloquent;

use App\Repository\EloquentRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Response;

class BaseRepository implements EloquentRepositoryInterface
{
    /** @var Model */
    protected $model;

    /**
     * BaseRepository constructor.
     *
     * @param Model $model
     */
    public function __construct(Model $model)
    {
        $this->model = $model;
    }

    /**
     * Transform some Postgres PDO driver's exceptions error message and code
     * 
     * @param \PDOException Postgres PDO driver exception
     * @return array $error[
     *    'text' => error message, 
     *    'httpStatus' => error code
     * ]   
     */
    protected function handlePDOExceptions(\PDOException $e): array
    {
        $error = array();
        $error['text'] = '';

        // Customize some poexception messages
        switch ($e->getCode()) {
        case '22P02':  // Ex. wrong UUID format
            $error['text'] = "Wrong parameters format. " . $e->errorInfo[2];
            $error['httpStatus'] = Response::HTTP_BAD_REQUEST;
            break;
        case '23503': // Foreign key violation
            $error['text'] = 'Object is been used by another resource';
            $error['httpStatus'] = 409;
            break;
        case '7':      // Postgres engine problem, bad databse name, server not found, wrogn credentials
            $error['text'] = 'Problem connecting to the database: ' . utf8_encode($e->errorInfo[2]);
            $error['httpStatus'] = Response::HTTP_INTERNAL_SERVER_ERROR;
            break;
        default:        // Other errors
            $error['text'] = utf8_encode($e->errorInfo[2]);
            $error['httpStatus'] = Response::HTTP_INTERNAL_SERVER_ERROR;
        }

        return $error;
    }

    /**
     * @inheritDoc
     */
    public function all(): Collection
    {
        try {
            return $this->model->all();

        } catch (\PDOException $e) {
            $error = $this->handlePDOExceptions($e);
            throw new \Exception($error['text'], $error['httpStatus']);
        }
    }

    /**
     * @inheritDoc
     */
    public function create(array $attributes): Model
    {
        try {
            return $this->model->create($attributes);

        } catch (\PDOException $e) {
            $error = $this->handlePDOExceptions($e);
            throw new \Exception($error['text'], $error['httpStatus']);
        }
    }

    /**
     * @inheritDoc
     */
    public function insert(array $data): void
    {
        try {
            $this->model->insert($data);

        } catch (\PDOException $e) {
            $error = $this->handlePDOExceptions($e);
            throw new \Exception($error['text'], $error['httpStatus']);
        }
    }

    /**
     * @inheritDoc
     */
    public function find(mixed $id): Model
    {
        try {
            return $this->model->findOrFail($id);

        } catch (\PDOException $e) {
            $error = $this->handlePDOExceptions($e);
            throw new \Exception($error['text'], $error['httpStatus']);
        }
    }

    /**
     * @inheritDoc
     */
    public function findOrNull(mixed $id): ?Model
    {
        try {
            return $this->model->find($id);

        } catch (\PDOException $e) {
            $error = $this->handlePDOExceptions($e);
            throw new \Exception($error['text'], $error['httpStatus']);
        }
    }

    /**
     * @inheritDoc
     */
    public function findByFields(array $filters): ?Model
    {
        try {
            /** @var Model $item */
            $item = $this->model->where($filters)->get()->first();

            return $item;

        } catch (\PDOException $e) {
            $error = $this->handlePDOExceptions($e);
            throw new \Exception($error['text'], $error['httpStatus']);
        }
    }

    /**
     * @inheritDoc
     */
    public function update(array $attributes, mixed $id): Model
    {
        try {
            /** @var Model $item */
            $item = $this->model->findOrFail($id);
            $item->update($attributes);

            return $item;

        } catch (\PDOException $e) {
            $error = $this->handlePDOExceptions($e);
            throw new \Exception($error['text'], $error['httpStatus']);
        }
    }

    /**
     * @inheritDoc
     */
    public function updateOrCreate(array $filters, array $attributes): Model
    {
        try {
            return $this->model->updateOrCreate($filters, $attributes);

        } catch (\PDOException $e) {
            $error = $this->handlePDOExceptions($e);
            throw new \Exception($error['text'], $error['httpStatus']);
        }
    }

    /**
     * @inheritDoc
     */
    public function delete($ids): int
    {
        try {
            return $this->model->destroy($ids);

        } catch (\PDOException $e) {
            $error = $this->handlePDOExceptions($e);
            throw new \Exception($error['text'], $error['httpStatus']);
        }
    }

    /**
     * @inheritDoc
     */
    public function upsert(array $values, $uniqueBy, $update = null)
    {
        try {
            return $this->model->upsert($values, $uniqueBy, $update);

        } catch (\PDOException $e) {
            $error = $this->handlePDOExceptions($e);
            throw new \Exception($error['text'], $error['httpStatus']);
        }
    }
}
