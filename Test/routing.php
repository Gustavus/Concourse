<?php
return [
  '/' => [
      'handler' => '\Gustavus\Concourse\Test\RouterTestController:index',
    ],
  '/indexTwo/{id}' => [
      'handler' => '\Gustavus\Concourse\Test\RouterTestController:indexTwo',
    ],
];