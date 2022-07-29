<?php

namespace App\Jobs;

use App\Repository\FilesRepositoryInterface;
use Exception;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Http\File;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Throwable;

class StoreFile implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /** 
     * File information 
     * 
     * @var array $fileData 
     */
    public $fileData;

     /**
     * The number of times the job may be attempted.
     *
     * @var int
     */
    public $tries = 3;
    
    /**
     * The number of seconds the job can run before timing out.
     *
     * @var int
     */
    public $timeout = 180;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct($fileData)
    {
        $this->fileData = $fileData;
        $this->onConnection('database');
    }

    /**
     * Calculate the number of seconds to wait before retrying the job.
     *
     * @return array
     */
    public function backoff()
    {
        return [3, 10, 20];
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        // Store file in "/plain" folder with MD5 values as filename
        Storage::putFileAs('plain', new File($this->fileData['localPath']), $this->fileData['file_md5']);
        if (file_exists($this->fileData['localPath'])) {
            unlink($this->fileData['localPath']);
        }
    }

    /**
     * Handle a job failure.
     *
     * @param  \Throwable  $exception
     * @return void
     */
    public function failed(Throwable $exception)
    {
        // Log error: failed to stored
        Log::error('Failed to store a file after 3 tries', [
            'file_record_id' => $this->fileData['file_id'],
            'file_md5'      => $this->fileData['file_md5'],
            'file_name'     => $this->fileData['file_name'],
            'error_code'    => $exception->getCode(),
            'error_message' => $exception->getMessage()
        ]);

        // Remove file's record from database and notify user
        $fileRepository = resolve(FilesRepositoryInterface::class);
        $fileRepository->fileFails($this->fileData['file_id']);

        // Free server resources: delete temporary file
        if (file_exists($this->fileData['localPath']))
            unlink($this->fileData['localPath']);
    }
}
