<?php

namespace App\Traits;

trait ApiResponse
{
    public function success($data, $code = 200)
    {
        return response()->json($data, $code);
    }

    public function error($message, $code = 400)
    {
        return response()->json(['error' => $message], $code);
    }
}
