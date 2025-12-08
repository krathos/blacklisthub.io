<?php

namespace App\Helpers;

class ApiResponse
{
    public static function success($data = [], $message = 'Success', $status = 200)
    {
        return response()->json([
            'success' => true,
            'status' => $status,
            'message' => $message,
            'data' => $data
        ], $status);
    }

    public static function error($message = 'Error', $status = 400, $data = [])
    {
        return response()->json([
            'success' => false,
            'status' => $status,
            'message' => $message,
            'data' => $data
        ], $status);
    }
}