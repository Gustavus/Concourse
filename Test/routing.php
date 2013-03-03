<?php
return [
  'index' => [
      'route' => '/',
      'handler' => '\Gustavus\Concourse\Test\RouterTestController:index',
  ],
  'indexTwo' => [
      'route' => '/indexTwo/{id}',
      'handler' => '\Gustavus\Concourse\Test\RouterTestController:indexTwo',
  ],
  'indexTwoKey' => [
    'route'   => '/indexTwo/{id}/{key}',
    'handler' => '\Gustavus\Concourse\Test\RouterTestController:indexThree',
  ],
];