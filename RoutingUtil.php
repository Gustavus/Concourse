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
   * @param  boolean $fullUrl   Whether you want the full url or just the relative url
   *
   * @throws  OutOfBoundsException If the alias cannot be found in the routing configuration
   * @return string built url
   */
  public static function buildUrl($routeConfig, $alias = '/', array $parameters = array(), $baseDir = '', $fullUrl = false)
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
          $route = preg_replace("`{{$key}.*?(?:}+)?}`", urlencode($param), $route);
        }
      }
      $url = str_replace('//', '/', $baseDir . $route);
      if ($fullUrl) {
        // $_SERVER variables that contain host information
        $serverHostVars = ['HTTP_HOST', 'SERVER_NAME', 'HOSTNAME', 'HOST'];
        $host = null;
        // look for our host info in $_SERVER
        foreach ($serverHostVars as $serverVar) {
          if (isset($_SERVER[$serverVar])) {
            $host = $_SERVER[$serverVar];
            break;
          }
        }
        if (empty($host)) {
          $host = (\Config::isBeta()) ? 'beta.gac.edu' : 'gustavus.edu';
        }
        return sprintf(
            'https://%s%s',
            $host,
            $url
        );
      }
      return $url;
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
    return Router::runHandler($alias, $foundRouteConfig, $parameters);
  }

  /**
   * Finds the alias for a route in the routing configuration given the handler
   *
   * @param  array  $routeConfig Full routing array
   * @param  string $handler     Handler we are wanting to find
   *
   * @return string|array String if the handler is found, array if not.
   */
  private static function findForwardingHandlerAlias($routeConfig, $handler)
  {
    foreach ($routeConfig as $alias => $config) {
      if (isset($config['handler']) && $handler === $config['handler']) {
        return $alias;
      }
    }
    // not found, return a default public config array
    return [
      'handler' => $handler,
    ];
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