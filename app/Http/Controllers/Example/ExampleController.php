<?php

namespace App\Http\Controllers\Example;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class ExampleController extends Controller
{

    private $exampleService;
    public function __construct(ExampleService $exampleService)
    {
        $this->exampleService = $exampleService;
    }
    public function exampleMethod(){
        return $this->exampleService->exampleMethod();
    }


}
