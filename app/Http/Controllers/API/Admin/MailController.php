<?php

namespace App\Http\Controllers\API\Admin;

use App\ApiResponse;
use App\Http\Controllers\Controller;
use App\Models\UserMail;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use App\Services\MailService;

class MailController extends Controller
{
    protected $mailService;

    public function __construct(MailService $mailService)
    {
        $this->mailService = $mailService;
    }

    public function userSendMail(Request $request)
    {
        $firstName = $request->first_name;
        $lastName = $request->last_name;
        $from = $request->email;
        $phone = $request->phone;
        $comment = $request->comment;

        $name = $firstName . ' ' . $lastName;
        $to = env('SENDER_MAIL', 'example@gmail.com');
        $subject = "User Suggestion";

        $validator = Validator::make($request->all(), [
            'first_name' => 'required',
            'last_name' => 'required',
            'email' => 'required|email',
            'phone' => 'required',
            'comment' => 'required'
        ]);

        if ($validator->fails()) {
            return ApiResponse::error(null, 'Field validation error', 400);
        }

        $body = $this->sendEmailTemplate($name, $comment, $from, $phone);
        $result = $this->mailService->sendMail($to, $subject, $body, true, $from, $name);


        if ($result['status'] == true) {
            UserMail::create([
                'name' => $name,
                'phone_number' => $phone,
                'email' => $from,
                'comment' => $comment
            ]);

            return ApiResponse::success(null, 'Successfully sent your suggestion mail', 200);
        }
        return ApiResponse::error($result['data'], 'Suggestion sending fail', 400);
    }

    private function sendEmailTemplate($name, $comment, $from, $phone)
    {
        return '
        <!DOCTYPE html>
        <html>
        <head>
            <style>
                body {
                    font-family: Arial, sans-serif;
                    background-color: #f4f4f4;
                    margin: 0;
                    padding: 0;
                }
                .container {
                    width: 80%;
                    margin: 0 auto;
                    background-color: #ffffff;
                    padding: 20px;
                    border-radius: 10px;
                    box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
                    border-style: ridge;
                }
                .header {
                    text-align: center;
                    background-color: #ddd;
                    color: black;
                    border-radius: 10px 10px 0 0;
                }
                .content {
                    padding: 20px;
                    text-align: left;
                }
                .footer {
                    padding: 20px;
                    text-align: left;
                    border-radius: 0 0 10px 10px;
                }
                .button {
                    display: inline-block;
                    padding: 10px 20px;
                    font-size: 16px;
                    color: #fff;
                    background-color: #4CAF50;
                    text-decoration: none;
                    border-radius: 5px;
                    margin-top: 20px;
                }
            </style>
        </head>
        <body>
            <div class="container">
                <div class="header">
                </div>
                <div class="content">
                    <p>Dear Sir or Madam,</p>
                    <p>I would like to say that about the following:</p>
                    <p>' . $comment . '</p>
                </div>
                <div class="footer">
                    <strong>Best regards,</strong><br/>
                    <span>' . $name . '</span><br/>
                    <span>Mail: </span><span>' . $from . '</span><br/>
                    <span>Phone: </span><span>' . $phone . '</span>
                </div>
            </div>
        </body>
        </html>';
    }
}
