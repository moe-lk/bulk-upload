<?php

namespace App\Mail;

use App\Models\User;
use App\Models\Institution_class;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Mail\Mailer as MailerContract;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Contracts\Queue\ShouldQueue;

class StudentCountExceeded extends Mailable
{
    use Queueable, SerializesModels;

    /**
     * Create a new message instance.
     *
     * @return void
     */
    public function __construct($file)
    {
//        $this->subject = 'SIS Bulk upload: Student count exceeded' . date('Y:m:d H:i:s');
//        $this->from = env('MAIL_USERNAME');
//        $this->to = [$user->first_name, $user->email];
//        $this->viewData = [
//            'name'=>$user->first_name, "body" => "The class you tried to import data is exceeded the student count limit.Please check the class / increase the student limit"
//        ];

        $institution = Institution_class::find($file['institution_class_id']);

        $this->user = User::find($file['security_user_id']);
        $this->subject = 'SIS Bulk Upload: Upload Failed '.$institution->institution->code.': '. $institution->name . date('Y:m:d H:i:s');
        $this->from_address = env('MAIL_FROM_ADDRESS');
        $this->from_name = 'SIS Bulk Uploader';
        $this->with = [
            'name' => $this->user->first_name,
            'link' =>   \App::environment('local') || \App::environment('stage')    ?  env('APP_URL').'/download/' .$file['filename'] : env('APP_URL').'/bulk-upload/download/' .$file['filename']
        ];
        $this->viewData = [
            'name'=>$this->user->first_name, "body" => "The class you tried to import data is exceeded the student count limit.Please check the class / increase the student limit",
            'link' =>   \App::environment('local') || \App::environment('stage')    ?  env('APP_URL').'/download/' .$file['filename'] : env('APP_URL').'/bulk-upload/download/' .$file['filename']
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
