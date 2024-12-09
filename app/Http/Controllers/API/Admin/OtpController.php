<?php

namespace App\Http\Controllers\API\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\ApiResponse;
use App\Services\MailService;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Validator;
use App\Models\OTP;
use App\Models\User;

class OtpController extends Controller
{
    protected $mailService;

    public function __construct(MailService $mailService)
    {
        $this->mailService = $mailService;
    }

    public function requestOTP(Request $request)
    {

        $validator = Validator::make($request->all(), [
            'email' => 'required'
        ]);

        if ($validator->fails()) {
            return ApiResponse::error(null, "Field validation error", 400);
        }

        if (!$request->context && $request->context != 'fpass')    //only use for classify register or forget pass
        {
            $registerd_user = User::where('email', $request->email)->first();
            if ($registerd_user) {
                return ApiResponse::error(null, 'Your mail is already registered', 400);
            }
        }

        $otp = random_int(100000, 999999);

        $to = $request->email;
        $subject = 'Laravel OTP';
        $body = $this->getOtpEmailTemplate($otp);
        $from = env('SENDER_MAIL', 'example@gmail.com');

        $result = $this->mailService->sendMail($to, $subject, $body, true, $from);

        if ($result['status'] == true) {
            OTP::create([
                'email' => $to,
                'otp' => $otp,
                'expired_at' => Carbon::now()->addMinutes(10)
            ]);

            return ApiResponse::success(null, 'Successfully sent OTP to your mail', 200);
        }

        return ApiResponse::error($result['data'], 'OTP sending fial', 400);
    }

    public function verifyOtp(Request $request)
    {
        $validator = Validator::make($request->all(), [
            "email" => "required",
            "otp" => "required"
        ]);

        if ($validator->fails()) {
            return ApiResponse::error(null, 'Fields validation error', 400);
        }

        $result = OtpValidate($request->all());

        if ($result) {
            return ApiResponse::success(null, "OTP verifcation successful", 200);
        } else {
            return ApiResponse::error(null, "OTP verification fail", 400);
        }
    }

    private function getOtpEmailTemplate($otp)
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
                }
                .header {
                    text-align: center;
                    padding-top: 30%;
                    background-color: #ddd;
                    color: black;
                    border-radius: 10px 10px 0 0;
                    background-image: url("' . env('MAIL_PROJECT_LOGO', 'your-image.png') . '");
                    
                    background-position: center;
                    background-repeat: no-repeat; 
                    background-size: cover; 
                    width: 700px;
                    height: 70px;
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
                    <p>Your OTP code is <strong>' . $otp . '</strong>.</p>
                    <p>Please use this code to complete your authentication process. The code is valid for 10 minutes.</p>
                    <strong>' . env('MAIL_WEBSITE_URL', 'https://www.google.com/') . '</strong>
                    <br/>
                </div>
                <div class="footer">
                    <strong>Best regards,</strong>
                    <p>Dashboard</p>
                </div>
            </div>
        </body>
        </html>';
    }
}
