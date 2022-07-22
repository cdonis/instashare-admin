<?php

namespace App\Repository\Eloquent;

use App\Models\File;
use App\Notifications\FileUploadStatusNotification;
use Illuminate\Http\Request;
use App\Repository\Eloquent\BaseRepository;
use App\Repository\FilesRepositoryInterface;
use Exception;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;

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

    /**
     * @inheritDoc
     */
    public function sendFileParsingResultNotification(int $file_id, string $file_status): void
    {
        $user = null;
        try {
            /** @var File $file */
            $file = $this->findOrNull($file_id);
            if ($file) {
                // Notify user about failure/succes of file's archive/compression process 
                $user = $file->user;
                Notification::send($user, new FileUploadStatusNotification([
                    'user_name' => $user->name,
                    'file_name' => $file->name,
                    'result'    => ($file_status === 'FAILED') ? 'FAILED' : 'SUCCESS' 
                ]));
            }
        } catch (Exception $e) {
            Log::error('User notification about file\'s archive/compression process fails', [
                'file_id' => $file->id,
                'user_id' => ($user) ? $user->id : 'UNKNOWN',
                'process_status' => ($file_status === 'FAILED') ? 'FAILED' : 'SUCCESS',
                'error_code' => $e->getCode(),
                'error_message' => $e->getMessage(),
            ]);
        }
    }

    /**
     * @inheritDoc
     */
    public function fileFails(int $id): void
    {
        try {
            $this->sendFileParsingResultNotification($id, 'FAILED');

            $file = $this->findOrNull($id);
            if ($file) {
                // Cleaning operation: 
                // If exists, try to remove corresponding files from S3 files system: could ocurrs in an interrupted process
                try {
                    Storage::delete("plain/{$file->md5}");
                    Storage::delete("zipped/{$file->md5}");

                } catch (Exception $e) {
                    // Do nothing.
                    // This "try - catch" is to avoid the use of "Storage::exists" function which is highly time consuming 
                }

                // Delete file's record from database
                $file->delete();
            }
        } catch (Exception $e) {
            Log::error('Deleting-file/cleaning operation after a failed file archive/compression process fails', [
                'file_id' => $id,
                'error_code' => $e->getCode(),
                'error_message' => $e->getMessage() 
            ]);
        }
    }
  
}
