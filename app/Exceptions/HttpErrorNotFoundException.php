<?php

namespace App\Exceptions;

use Exception;

class HttpErrorNotFoundException extends Exception
{
    public function __construct(protected $message = 'Resource not found'){}

    public function render(){
        return response()->json(['message' => $this->message], 404);
    }
}
