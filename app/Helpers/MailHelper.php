<?php
namespace App\Helpers;
use Illuminate\Support\Facades\Mail;

class MailHelper
{
    public static function send($to, $mailable)
    {
        if (!env('MAIL_ENABLED', true)) {
            return;
        }

        Mail::to($to)->send($mailable);
    }

    public static function queue($to, $mailable)
    {
        if (!env('MAIL_ENABLED', true)) {
            return;
        }

        Mail::to($to)->queue($mailable);
    }
}