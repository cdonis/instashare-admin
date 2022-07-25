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

    /**
     * Send a user notification about result of file's archive/compression process. Notifications are only sent when the
     * file fails at any stage (archive or compression) or when the process completes (file zipped). When intermediate 
     * step is done, i.e. when file finishes in-S3 storing operation, a notification is not sent.
     * 
     * This function is only used on asynchronous operations. Exceptions are catched and reported to log file
     * 
     * @param int       $file_id        ID of processed file 
     * @param string    $file_status    File status: 'FAILED' | 'ZIPPED'
     * @throws null     	            Exceptions are catched and reported to log file
     * @return void
     */
    public function sendFileParsingResultNotification(int $file_id, string $file_status): void;
    
    /**
     * Due to a failure in the file's archive/compression process, send a user notification and delete file's record in database
     * 
     * This function is only used on asynchronous operations. Exceptions are catched and reported to log file
     * 
     * @param int       $id             ID of failed file
     * @throws null                     Exceptions are catched and reported to log file
     * @return void
     */
    public function fileFails(int $id): void;

    /**
     * Remove file's record from database and corresponding file from S3 file system
     * 
     * @param int       $id             ID of failed file
     */
    public function removeFile(int $id): void;
  
}
