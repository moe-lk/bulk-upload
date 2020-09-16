<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Contracts\Queue\ShouldQueue;

class ExportReady extends Mailable
{
    use Queueable, SerializesModels;

    /**
     * Create a new message instance.
     *
     * @return void
     */
    public function __construct($user)
    {
        $this->user = $user;
        $this->subject = 'The DoE data is ready to download '. date('Y:m:d H:i:s');
        $this->from_address = env('MAIL_FROM_ADDRESS');
        $this->from_name = 'SIS Bulk Uploader';
        $this->with = [
            'name' => $this->user->first_name,
            'link' => \App::environment('local') || \App::environment('stage')   ?  env('APP_URL').'/downloadExportexamination' : env('APP_URL').'/bulk-upload/downloadExportexamination'
        ];

        $this->viewData = [
            'name'=>$this->user->first_name, "body" =>'Your requested file is ready to download',
            'link' => \App::environment('local') || \App::environment('stage')   ?  env('APP_URL').'/downloadExportexamination' : env('APP_URL').'/bulk-upload/downloadExportexamination'
        ];
    }

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build()
    {
        return $this->view('emails.mail')
            ->from($this->from_address,$this->from_name)
            ->to($this->user->email)
            ->subject($this->subject)
            ->with($this->with);
    }
}
