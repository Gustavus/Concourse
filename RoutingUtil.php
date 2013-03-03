<?php
/**
 * @package Concourse
 * @author Billy Visto
 */

namespace Gustavus\Concourse;

require_once 'gatekeeper/gatekeeper.class.php';

use Gustavus\Gatekeeper\Gatekeeper,
  TemplatePageRequest;

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
   * @param  array  $routeConfig Full routing array
   * @param  string $alias       alias to build url for
   * @param  array  $parameters  parameters to build url with. keyed by route param name
   * @return string built url
   */
  public static function buildUrl(array $routeConfig, $alias = '/', array $parameters = array())
  {
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
    }
    return '/';
  }

  /**
   * Forwards a request onto a different handler
   *
   * @param  array  $routeConfig Full routing array
   * @param  string $alias       alias to forward to
   * @param  array  $parameters  parameters to send to the handler
   * @return mixed
   */
  public static function forward(array $routeConfig, $alias = '/', array $parameters = array())
  {
    if (isset($routeConfig[$alias])) {
      return Router::runHandler($routeConfig[$alias], $parameters);
    }
  }
}