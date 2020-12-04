<?php

namespace App\Mail;

use App\Contact;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Contracts\Queue\ShouldQueue;

class sendRequest extends Mailable
{
    use Queueable, SerializesModels;
    public $contact;
    public $code;
    /**
     * Create a new message instance.
     *
     * @return void
     */
    public function __construct(Contact $contact,$code)
    {
        $this->contact=$contact;
        $this->code=$code;
    }

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build()
    {
        return $this->from('ec.ioptime@gmail.com')->subject('Contact Request')->markdown('emails.sendRequest');
    }


}
