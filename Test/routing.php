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
];