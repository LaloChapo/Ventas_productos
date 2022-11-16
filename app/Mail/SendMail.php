<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class SendMail extends Mailable
{
    use Queueable, SerializesModels;
     
     public $orders;

    /**
     * Create a new message instance.
     *
     * @return void
     */
    public function __construct($orders)
    {
        //
        $this->orders=$orders;
    }

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build()
    {
        return $this->from('joseeduardo.diazlorenzo.18e30258@gmail.com')->subject('Purchased product')->view('mail.invoice')->with('orders',$this->orders);
    }
}
