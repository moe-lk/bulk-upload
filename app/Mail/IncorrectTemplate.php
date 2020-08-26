<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Contracts\Queue\ShouldQueue;

class IncorrectTemplate extends Mailable
{
    use Queueable, SerializesModels;

     /**
     * Create a new message instance.
     *
     * @return void
     */
    public function __construct($file)
    {

        $institution = Institution_class::find($file['institution_class_id']);


        $this->user = User::find($file['security_user_id']);
        $this->subject = 'SIS Bulk Upload: IncorrectTemplate'.$institution->institution->code.': '. $institution->name.' '. date('Y:m:d H:i:s');
        $this->from_address = env('MAIL_FROM_ADDRESS');
        $this->from_name = 'SIS Bulk Uploader';
        $this->with = [
            'name' => $this->user->first_name,
            'link' => \App::environment('local') || \App::environment('stage')  ?  env('APP_URL').'/download/' .$file['filename'] : env('APP_URL').'/bulk-upload/download/' .$file['filename']
        ];
        $this->viewData = [
            'name'=>$this->user->first_name, "body" => "The Template you used for upload having some issues, please reconfirm and re-upload with correct template",
            'link' =>  \App::environment('local') || \App::environment('stage')   ?  env('APP_URL').'/download/' .$file['filename'] : env('APP_URL').'/bulk-upload/download/' .$file['filename']
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
