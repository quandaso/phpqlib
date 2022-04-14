<?php
/**
 * Created by PhpStorm.
 * User: quantm
 * Date: 12/31/2016
 * Time: 10:32 AM
 */

namespace Q\Helpers;


use Q\Response\ViewResponse;
use PHPMailer\PHPMailer\PHPMailer;
class Mail
{
    /**
     * @param $toEmail
     * @param $subject
     * @param $body ViewResponse| string
     * @param array|null $attachments
     * @return string
     * @throws \Exception
     * @throws \phpmailerException
     */
    public static function send($toEmail, $subject, $body, $attachments = null) {
        $mail = new PHPMailer();
        $mail->CharSet = 'UTF-8';
        $mailConfig = env('MAIL');

        if ($body instanceof ViewResponse) {
            $body = $body->renderViewAsString();
        }

        if ($mailConfig['smtp']) {
            $mail->isSMTP();
            // Set mailer to use SMTP
            $mail->Host = $mailConfig['host'];  // Specify main and backup SMTP servers
            $mail->SMTPAuth = true;                               // Enable SMTP authentication
            $mail->Username = $mailConfig['username'];                 // SMTP username
            $mail->Password = $mailConfig['password'];                           // SMTP password
            //  $mail->SMTPSecure = 'tls';                            // Enable TLS encryption, `ssl` also accepted
            $mail->Port = (int)$mailConfig['port'];                                    // TCP port to connect to
        }

        $mail->setFrom('no-reply@finexpress.ph', 'FinExpress');
        $mail->addAddress($toEmail);     // Add a recipient
        $mail->Subject = $subject;
        $mail->Body    = $body;
        $mail->AltBody = $subject;

        if (!empty ($attachments)) {
            foreach ((array)$attachments as $file) {
                $mail->addAttachment($file);
            }
        }

        if (!$mail->send()) {

            //echo 'Message could not be sent.';
            return ('Mailer Error: ' . $mail->ErrorInfo);
        } else {

            ///echo 'Message has been sent';
            return 'Sent email to: ' . $toEmail;
        }
    }
}