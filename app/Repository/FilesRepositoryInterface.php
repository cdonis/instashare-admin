<?php

namespace App\Repository;

use Illuminate\Http\Request;

/**
 * Interface FilesRepositoryInterface
 */
interface FilesRepositoryInterface extends EloquentRepositoryInterface
{
  /**
   * Return the list of files according to filtering, pagination and sorting criterias
   * @param Request $request
   */
  public function getList(Request $request): array;
  
}
