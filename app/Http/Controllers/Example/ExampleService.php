<?php

namespace App\Http\Controllers\Example;

class ExampleService
{
    private $exampleQuery;
    public function __construct(ExampleQuery $exampleQuery)
    {
        $this->exampleQuery = $exampleQuery;
    }
    public function exampleMethod(){
        return $this->exampleQuery->exampleMethodQuery();
    }

}
