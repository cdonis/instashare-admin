<?php

namespace App\Jobs;

use App\Repository\FilesRepositoryInterface;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Throwable;

class UpdateFileStatus implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /** 
     * File status 
     * [
     *      file_id     => int,
     *      file_md5    => string,
     *      file_name   => string,
     *      file_status => 'ZIPPED' | 'FAILED',
     *      file_size   => int | null
     * ]
     * @var array $fileData 
     */
    public $fileStatus;

    /**
     * The number of times the job may be attempted.
     *
     * @var int
     */
    //public $tries = 3;
    
    /**
     * The number of seconds the job can run before timing out.
     *
     * @var int
     */
    //public $timeout = 30;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct($fileStatus)
    {
        $this->fileStatus = $fileStatus;
    }

    /**
     * Calculate the number of seconds to wait before retrying the job.
     *
     * @return array
     */
    /*
    public function backoff()
    {
        return [3, 5, 10];
    }
    */

    /**
     * Execute the job. Update status and send notification to user when fails or finishes
     *
     * @return void
     */
    public function handle()
    {
        /** @var FilesRepositoryInterface $fileRepository */
        $fileRepository = resolve(FilesRepositoryInterface::class);
        
        // If new status is FAILED then notify user and delete file's record, else update status
        if ($this->fileStatus['file_status'] === 'FAILED') {
            $fileRepository->fileFails($this->fileStatus['file_id']);
        } else {
            $data = ['status' => $this->fileStatus['file_status']];
            if ($this->fileStatus['file_size']) $data['size'] = $this->fileStatus['file_size'];
            
            // Update status
            $fileRepository->update($data, $this->fileStatus['file_id']);

            // Send user notification if archive/compression finishes OK: file status = 'ZIPPED'
            if ($this->fileStatus['file_status'] === 'ZIPPED') {
                $fileRepository->sendFileParsingResultNotification($this->fileStatus['file_id'], 'ZIPPED');
            }
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
        $errorInfo = [
            'file_id'           => $this->fileStatus['file_id'],
            'file_md5'          => $this->fileStatus['file_md5'],
            'file_name'         => $this->fileStatus['file_name'],
            'new_status'        => $this->fileStatus['file_status'],
            'error_code'        => $exception->getCode(),
            'error_message'     => $exception->getMessage()
        ];
        if ($this->fileStatus['file_size']) $errorInfo['new_file_size'] = $this->fileStatus['file_size'];

        // Log error: failed to update file status
        Log::error('Failed to update file status', $errorInfo);
    }
}
