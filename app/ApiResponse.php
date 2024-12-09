<?php

namespace App;

class ApiResponse
{
    public static function success($data, $message = 'Request was successful', $status = 200)
    {
        $data = [
            'status' => true,
            'message' => $message,
            'data' => $data,
        ];

        return response()->json($data, $status);        
    }

    public static function error($data = null, $message, $status = 400)
    {
        $err = [
            'status' => false,
            'data' => $data,
            'message' => $message,
        ];

        return response()->json($err, $status);        
    }
}
