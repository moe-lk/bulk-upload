<?php

namespace App\Mail;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Mail\Mailer as MailerContract;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class StudentImportSuccess extends Mailable
{
    use Queueable, SerializesModels;

    /**
     * Create a new message instance.
     *
     * @return void
     */
    public function __construct($user)
    {
        $this->user = User::find($user['security_user_id']);
        $this->subject = 'SIS Bulk upload: Student upload success ' . date('Y:m:d H:i:s');
        $this->from_address = env('MAIL_USERNAME');
        $this->from_name = 'SIS Bulk Uploader';
        $this->with = [
            'name' => $this->user->first_name,
            'link' =>  env('APP_URL').'/create/'
        ];
        $this->viewData = [
            'name'=>$this->user->first_name, "body" => "Student upload success, you can access the data from open email UI and dashboard",
            'link' =>  env('APP_URL').'/create/'
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
