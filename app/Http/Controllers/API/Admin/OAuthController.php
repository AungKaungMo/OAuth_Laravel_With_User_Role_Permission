<?php

namespace App\Http\Controllers\API\Admin;

use Exception;
use Illuminate\Http\Request;
use League\OAuth2\Client\Provider\Exception\IdentityProviderException;
use League\OAuth2\Client\Provider\Google;
use App\Http\Controllers\Controller;
use App\ApiResponse;

class OAuthController extends Controller
{
    private $client_id;
    private $client_secret;
    private $redirect_uri;

    private $provider;
    private $google_options;

    public function __construct()
    {
        $this->client_id = env('GMAIL_API_CLIENT_ID');
        $this->client_secret = env('GMAIL_API_CLIENT_SECRET');
        $this->redirect_uri = env('GMAIL_CALL_BACK_URL');
        $this->google_options = [
            'scope' => [
                'https://mail.google.com/'
            ],
            'access_type' => 'offline',  // Request offline access to get a refresh token
            'prompt' => 'consent' // Force consent to ensure refresh token is returned
        ];
        $params = [
            'clientId'      => $this->client_id,
            'clientSecret'  => $this->client_secret,
            'redirectUri'   => $this->redirect_uri,
            'accessType'    => 'offline'
        ];

        $this->provider = new Google($params);
    }

    public function doGenerateToken()
    {
        $redirect_uri = $this->provider->getAuthorizationUrl($this->google_options);
        return redirect($redirect_uri);
    }

    public function doSuccessToken(Request $request)
    {
        $code = $request->get('code');

        try {
            $tokenObj = $this->provider->getAccessToken(
                'authorization_code',
                [
                    'code' => $code
                ]
            );

            $token = $tokenObj->getToken();
            $refresh_token = $tokenObj->getRefreshToken();

            if ($refresh_token != null && !empty($refresh_token)) {
                return ApiResponse::success($refresh_token, 'Get refresh token success', 200);
            } elseif ($token != null && !empty($token)) {
                return ApiResponse::success($token, 'Get token success', 200);
            } else {
                return ApiResponse::error(null, 'Unable to retreive token', 400);
            }
        } catch (IdentityProviderException $e) {
            return ApiResponse::error($e->getMessage(), 'Error exception occur', 400);
        } catch (Exception $e) {
            return ApiResponse::error($e->getMessage(), 'Error exception occur', 400);
        }
    }
}
