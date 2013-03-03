<?php
/**
 * @package Concourse
 * @author Billy Visto
 */

namespace Gustavus\Concourse;

use OutOfBoundsException;

/**
 * Manages sending people to the requested page. Checks to see if the user has access to it first.
 *
 * @package  Concourse
 * @author  Billy Visto
 */
class RoutingUtil extends Router
{
  /**
   * Builds a url based on the alias and the route in $routeConfig
   *
   * @param  array|string  $routeConfig Full routing array or path to the full array
   * @param  string $alias       alias to build url for
   * @param  array  $parameters  parameters to build url with. keyed by route param name
   *
   * @throws  OutOfBoundsException If the alias cannot be found in the routing configuration
   * @return string built url
   */
  public static function buildUrl($routeConfig, $alias = '/', array $parameters = array())
  {
    if (!is_array($routeConfig)) {
      $routeConfig = include($routeConfig);
    }
    if (isset($routeConfig[$alias])) {
      $route = $routeConfig[$alias]['route'];
      if (strpos($route, '{') !== false) {
        // we need to fill the url with parameters
        foreach ($parameters as $key => $param) {
          if (strpos($route, "{{$key}}") !== false) {
            $route = str_replace("{{$key}}", $param, $route);
          }
        }
      }
      return $route;
    } else {
      throw new OutOfBoundsException("Alias: {$alias} not found in routing configuration");
    }
  }

  /**
   * Forwards a request onto a different handler
   *
   * @param  array|string  $routeConfig Full routing array or path to the full array
   * @param  string $alias       alias to forward to
   * @param  array  $parameters  parameters to send to the handler
   *
   * @throws  OutOfBoundsException If the alias cannot be found in the routing configuration
   * @return mixed
   */
  public static function forward($routeConfig, $alias = '/', array $parameters = array())
  {
    if (!is_array($routeConfig)) {
      $routeConfig = include($routeConfig);
    }
    if (isset($routeConfig[$alias])) {
      return Router::runHandler($routeConfig[$alias], $parameters);
    } else {
      throw new OutOfBoundsException("Alias: {$alias} not found in routing configuration");
    }
  }
}