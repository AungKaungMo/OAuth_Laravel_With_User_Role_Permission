<?php

namespace App\Services;

use Exception;
use League\OAuth2\Client\Provider\Google;
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\OAuth;

class MailService
{
    private $email;
    private $name;
    private $client_id;
    private $client_secret;
    private $refreshToken;

    public function __construct()
    {
        $this->email            = env('SENDER_MAIL'); 
        $this->name       = env('SENDER_NAME');    
        $this->client_id        = env('GMAIL_API_CLIENT_ID');
        $this->client_secret    = env('GMAIL_API_CLIENT_SECRET');
        $this->refreshToken = env('REFRESH_TOKEN');
    }

    public function sendMail($toMailAddress, $subject = null, $body = null, $isHtml = false, $fromMailAddress = null, $fromName = null)
    {

        $mail = new PHPMailer(true);

        try {
            $mail->isSMTP();
            $mail->Host       = 'smtp.gmail.com';
            $mail->Port       = 465;
            $mail->SMTPSecure = 'ssl';
            $mail->SMTPAuth   = true;
            $mail->AuthType   = 'XOAUTH2';

            $mail->setOAuth(
                new OAuth(
                    [
                        'provider' => new Google(
                            [
                                'clientId'     => $this->client_id,
                                'clientSecret' => $this->client_secret,
                            ]
                        ),
                        'clientId'        => $this->client_id,
                        'clientSecret'    => $this->client_secret,
                        'refreshToken'    => $this->refreshToken,
                        'userName'        => $this->email,
                    ]
                )
            );

            $mail->setFrom($fromMailAddress ?? $this->email, $fromName?? $this->name);
            $mail->addAddress($toMailAddress);
            $mail->Subject = $subject;
            $mail->Body    = $body;
            $mail->isHTML($isHtml);

            if (!$mail->send()) {
                return [
                    'status' => false,
                    'data' => $mail->ErrorInfo
                ];
            } else {
                return [
                    'status' => true
                ];
            }
        } catch (Exception $e) {
            return [
                'status' => false,
                'data' => $e->getMessage()
            ];
        }
    }
}