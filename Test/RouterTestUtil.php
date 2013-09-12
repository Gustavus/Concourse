<?php
/**
 * @package Concourse
 * @subpackage Test
 * @author  Billy Visto
 */

namespace Gustavus\Concourse\Test;

use OutOfBoundsException,
  Gustavus\Concourse\Router;

class RouterTestUtil extends Router
{
  /**
   * Forwards to the class given in the forwardingTestClassMapping
   *
   * @param  array|string $routeConfig Full routing array or path to the full array
   * @param  string $alias       alias to forward to
   * @param  array  $parameters  parameters to send to the handler
   * @param  array  $forwardingTestClassMapping Mapping from the non test class to the test class to forward to
   *
   * @throws  OutOfBoundsException If the alias cannot be found in the routing configuration
   * @throws  OutOfBoundsException If the handler cannot be found in the forwardingTestClassMapping
   * @return mixed
   */
  public static function forward($routeConfig, $alias = '/', array $parameters, $forwardingTestClassMapping)
  {
    if (!is_array($routeConfig)) {
      $routeConfig = include($routeConfig);
    }
    if (strpos($alias, ':') !== false) {
      // the alias is actually the handler that we want to forward onto.
      $alias = self::findForwardingHandlerAlias($routeConfig, $alias);
    }
    if (is_array($alias)) {
      // alias wasn't found, we got a default configuration array.
      $foundRouteConfig = $alias;
    } else if (isset($routeConfig[$alias])) {
      // alias exists
      $foundRouteConfig = $routeConfig[$alias];
    } else {
      // not found
      throw new OutOfBoundsException("Alias: {$alias} not found in routing configuration");
    }

    $handler = explode(':', $foundRouteConfig['handler'])[0];

    if (!isset($forwardingTestClassMapping[$handler])) {
      throw new OutOfBoundsException("The found handler: \"{$handler}\" could not be found in the forwardingTestClassMapping array");
    }

    $newHandler = $forwardingTestClassMapping[$handler];

    $foundRouteConfig['handler'] = str_replace($handler, $newHandler, $foundRouteConfig['handler']);

    return Router::runHandler($alias, $foundRouteConfig, $parameters);
  }
}