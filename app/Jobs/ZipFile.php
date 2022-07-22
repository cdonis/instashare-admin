<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class ZipFile implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /** 
     * File information 
     * [
     *      file_id     => int,
     *      file_md5    => string,
     *      file_name   => string
     * ] 
     * @var array $fileData 
     */
    public $fileData;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct($fileData)
    {
        $this->fileData = $fileData;
        $this->onConnection('rabbitmq');
        $this->onQueue('instashare_zipper');
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        //
    }
}
