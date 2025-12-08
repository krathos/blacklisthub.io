<?php

if (!function_exists('api_success')) {
    function api_success($data = [], $message = 'Success', $status = 200)
    {
        return \App\Helpers\ApiResponse::success($data, $message, $status);
    }
}

if (!function_exists('api_error')) {
    function api_error($message = 'Error', $status = 400, $data = [])
    {
        return \App\Helpers\ApiResponse::error($message, $status, $data);
    }
}