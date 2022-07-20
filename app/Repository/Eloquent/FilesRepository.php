<?php

namespace App\Repository\Eloquent;

use App\Models\File;
use Illuminate\Http\Request;
use App\Repository\Eloquent\BaseRepository;
use App\Repository\FilesRepositoryInterface;

/**
 * Repository for file's database related operations
 */

class FilesRepository extends BaseRepository implements FilesRepositoryInterface
{
  /**
   * Constructor
   * 
   * @param File $model
   */
  public function __construct(File $model)
  {
    parent::__construct($model);
  }

  /**
   * @inheritDoc
   */
  public function getList(Request $request): array
  {
    $filters = ($request->filter) ? \json_decode($request->filter, true) : [];              // Filtering criteria
    $defaultSort = [];                                                                    
    $sorters = ($request->sort) ? \json_decode($request->sort, true) : [];                  // Sorting criteria

    // Filtering by "keyword".
    $keyword = $request->input('keyword');
    $keywordSearchFields = ['"name"'];                          // Attributes to consider while filtering by "keyword".

    try {
      $data = $this->model
        ->withFiltering($filters)
        ->withSorting($defaultSort, $sorters)
        ->withKeywordSearch($keyword, $keywordSearchFields);

      $items = null;
      $total = 0;

      if (!empty($request->current) && !empty($request->pageSize)) {
        $paginator = $data->paginate($request->input('pageSize'), '[*]', 'current');
        $items = $paginator->items();
        $total = $paginator->total();
      } else {
        $items = $data->get()->toArray();
        $total = count($items);
      }

      return [
        'success' => true,
        'data' => $items,
        'total' => $total,
      ];
      
    } catch (\PDOException $e) {
      $error = $this->handlePDOExceptions($e);
      throw new \Exception($error['text'], $error['httpStatus']);
    }
  }
}
