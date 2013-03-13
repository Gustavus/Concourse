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
   * @param  string $baseDir    Application's root directory
   *
   * @throws  OutOfBoundsException If the alias cannot be found in the routing configuration
   * @return string built url
   */
  public static function buildUrl($routeConfig, $alias = '/', array $parameters = array(), $baseDir = '')
  {
    if (!is_array($routeConfig)) {
      $routeConfig = include($routeConfig);
    }
    if (empty($baseDir)) {
      $baseDir = dirname($_SERVER['SCRIPT_NAME']);
    }
    if (isset($routeConfig[$alias])) {
      $route = $routeConfig[$alias]['route'];
      if (strpos($route, '{') !== false) {
        // we need to fill the url with parameters
        foreach ($parameters as $key => $param) {
          $route = preg_replace("`{{$key}.*?}`", $param, $route);
        }
      }
      return str_replace('//', '/', $baseDir . $route);
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
    if (strpos($alias, ':') !== false) {
      // the alias is actually the handler that we want to forward onto.
      $routeConfig[$alias] = ['handler' => $alias];
    }
    if (isset($routeConfig[$alias])) {
      return Router::runHandler($routeConfig[$alias], $parameters);
    } else {
      throw new OutOfBoundsException("Alias: {$alias} not found in routing configuration");
    }
  }

  /**
   * Gets the breadCrumbs out of the routing configuration for a specific alias
   *
   * @param  array|string $routeConfig Full routing array or path to the full array
   * @param  string $alias       alias to get crumbs for
   *
   * @throws  OutOfBoundsException If the alias cannot be found in the routing configuration
   * @return array
   */
  public static function getBreadCrumbs($routeConfig, $alias = '/')
  {
    if (!is_array($routeConfig)) {
      $routeConfig = include($routeConfig);
    }
    if (isset($routeConfig[$alias])) {
      return isset($routeConfig[$alias]['breadCrumbs']) ? $routeConfig[$alias]['breadCrumbs'] : [];
    } else {
      throw new OutOfBoundsException("Alias: {$alias} not found in routing configuration");
    }
  }
}