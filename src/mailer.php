<?php

function send_mail(string $to, string $subject, string $body): bool
{
    if (config('env') !== 'prod') {                       // dev: log instead of send
        error_log("MAIL to=$to subj=$subject\n$body");
        return true;
    }
    $headers = 'From: ' . config('mail_from') . "\r\n"
             . "Content-Type: text/plain; charset=UTF-8\r\n";
    return mail($to, '=?UTF-8?B?' . base64_encode($subject) . '?=', $body, $headers);
}
