<?php

namespace App\Mail;

use App\Models\User;
use App\Models\Institution_class;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Contracts\Queue\ShouldQueue;

class EmptyFile extends Mailable
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
        $this->subject = 'SIS Bulk Upload: ' .$file['subject'].' Empty ' . $institution->institution->code.': '. $institution->name.' '. date('Y:m:d H:i:s');
        $this->from_address = env('MAIL_FROM_ADDRESS');
        $this->from_name = 'SIS Bulk Uploader';
        $this->with = [
            'name' => $this->user->first_name,
            'link' => \App::environment('local') || \App::environment('stage')   ?  env('APP_URL') : env('APP_URL').'/bulk-upload/'
        ];
        $this->viewData = [
            'name'=>$this->user->first_name, "body" => "No data Found in ". $file['filename']. ' Please re-upload the file with data',
            'link' =>  \App::environment('local') || \App::environment('stage')   ?  env('APP_URL') : env('APP_URL').'/bulk-upload/'
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
