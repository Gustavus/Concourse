<?php

namespace Gustavus\Concourse\Test;

class RouterTestController
{
  public function index()
  {
    return 'RouterTestController index()';
  }

  public function indexTwo($testArg)
  {
    return "RouterTestController indexTwo({$testArg})";
  }
}