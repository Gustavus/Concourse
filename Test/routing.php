<?php
/**
 * Routing file for testing
 *
 * @package Concourse
 * @subpackage Test
 * @author  Billy Visto
 */

return [
  'index' => [
      'route' => '/',
      'handler' => '\Gustavus\Concourse\Test\RouterTestController:index',
  ],
  'indexTwo' => [
      'route' => '/indexTwo/{id}',
      'handler' => '\Gustavus\Concourse\Test\RouterTestController:indexTwo',
      'breadCrumbs' => [['url' => 'Some Url', 'text' => 'text']],
  ],
  'indexTwoKey' => [
    'route'   => '/indexTwo/{id}/{key}',
    'handler' => '\Gustavus\Concourse\Test\RouterTestController:indexThree',
  ],
];