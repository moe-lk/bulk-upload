<?php

namespace App\Mail;

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
    public function __construct($user)
    {
        $this->subject = 'SIS Bulk upload: Student count exceeded' . date('Y:m:d H:i:s');
        $this->from = env('MAIL_USERNAME');
        $this->to = [$user->first_name, $user->email];
        $this->viewData = [
            'name'=>$user->first_name, "body" => "The class you tried to import data is exceeded the student count limit.Please check the class / increase the student limit"
        ];
    }

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build()
    {
        return $this->view('emails.mail');
    }

}
