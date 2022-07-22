<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class FileUploadStatusNotification extends Notification
{
    use Queueable;

    /**
     * Email data
     * [
     *      user_name => string,
     *      file_name => string, 
     *      result    => 'FAILED' | 'SUCCESS'  
     * ]
     * @var array $data
     */
    protected $data;

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
    public $timeout = 30;

    /**
     * Create a new notification instance.
     *
     * @return void
     */
    public function __construct($data)
    {
        $this->data = $data;
        $this->onConnection('database');
    }

    /**
     * Get the notification's delivery channels.
     *
     * @param  mixed  $notifiable
     * @return array
     */
    public function via($notifiable)
    {
        return ['mail'];
    }

    /**
     * Get the mail representation of the notification.
     *
     * @param  mixed  $notifiable
     * @return \Illuminate\Notifications\Messages\MailMessage
     */
    public function toMail($notifiable)
    {
        $result_line = ($this->data['result'] = 'SUCCESS')
            ? "File \"{$this->data['file_name']}\" you recently uploaded to our site has succesfully finished its archiving/compression process and now, it is available to download by the community."
            : "Unfortunately, the file \"{$this->data['file_name']}\" you recently uploaded to our site, failed the archiving/compression process. We are sorry for the inconvenience and we encourage you to upload it again.";

        return (new MailMessage)
                    ->greeting("Hi {$this->data['user_name']},")
                    ->line('Thank you for sharing your files with the InstaShare community !!!')
                    ->line($result_line)
                    ->line('We are very grateful to you.');
    }

    /**
     * Get the array representation of the notification.
     *
     * @param  mixed  $notifiable
     * @return array
     */
    public function toArray($notifiable)
    {
        return [
            //
        ];
    }
}
