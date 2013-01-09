<?php
/**
 * @package Concourse
 * @author Billy Visto
 */

namespace Gustavus\Concourse;

require_once 'gatekeeper/gatekeeper.class.php';

use Gustavus\Gatekeeper\Gatekeeper;

/**
 * @package  Concourse
 * @author  Billy Visto
 */
class Router
{
  /**
   * Handles the requested route and calls the respective controller
   *
   * @param  array  $routingConfig Keyed by route values are arrays with options of handler and security
   * @param  string $route The path from the application's root that the user is requesting
   * @return string
   */
  public static function handleRequest(array $routingConfig, $route)
  {
    if (strpos($route, '/') !== 0) {
      // all of the routingConfig indexes should be from the application root
      $route = '/' . $route;
    }

    if (isset($routingConfig[$route])) {
      // basic route
      return Router::runHandler($routingConfig[$route]);
    } else if (($advancedRoute = Router::findAdvancedRoute(array_keys($routingConfig), $route)) !== false) {
      // could potentially be a more advanced route
      return Router::runHandler($routingConfig[key($advancedRoute)], $advancedRoute);
    } else {
      // route not found
      header('HTTP/1.0 404 Not Found');
      ob_start();

      $_SERVER['REDIRECT_STATUS'] = 404;
      include '/cis/www/errorPages/error.php';

      ob_end_flush();
      exit;


      // header("HTTP/1.0 404 Not Found");
      // ob_start();

      // $_SERVER['REDIRECT_STATUS'] = 403;
      // $_SERVER['REDIRECT_URL'] = $_SERVER['HTTP_REFERER'];
      // \Gustavus\Extensibility\Filters::add('localNavigation', 'localNavigation');
      // include_once('/cis/www/errorPages/error.php');

      // ob_end_flush();
      // exit;
    }
  }

  /**
   * Runs the handler set in $routeConfig
   *
   * @param  array $routeConfig
   * @param  array $args  arguments to pass onto the controller
   * @return string false if user can't access page. String otherwise
   */
  private static function runHandler(array $routeConfig, array $args = array())
  {
    if (!Router::userCanAccessPage($routeConfig)) {
      header("HTTP/1.0 403 Forbidden");
      ob_start();

      $_SERVER['REDIRECT_STATUS'] = 403;
      $_SERVER['REDIRECT_URL'] = $_SERVER['HTTP_REFERER'];
      \Gustavus\Extensibility\Filters::add('localNavigation', 'localNavigation');
      include_once('/cis/www/errorPages/error.php');

      ob_end_flush();
      exit;
    }

    $handler = explode(':', $routeConfig['handler']);
    if (empty($args)) {
      return call_user_func(array(new $handler[0], $handler[1]));
    }
    return call_user_func(array(new $handler[0], $handler[1]), implode(', ', array_values(current($args))));

  }

  /**
   * Finds advanced routing
   *
   * @param  array $routes
   * @param  string $route
   * @return array|boolean Array if a route was found, false otherwise
   */
  private static function findAdvancedRoute(array $routes, $route)
  {
    // first lets split the route up
    $splitRoute = explode('/', trim($route, '/'));

    foreach ($routes as $key) {
      $splitKey = explode('/', trim($key, '/'));
      if (count($splitKey) === count($splitRoute)) {
        // we might have a match. Let's look to see how close they match
        $analyzeResult = Router::analyzeSplitRoutes($splitKey, $splitRoute);
        if ($analyzeResult !== false) {
          // we have a match!
          return [$key => $analyzeResult];
        }
      }
    }
    // nothing found
    return false;
  }

  /**
   * Checks to see if the route matches the current route in the routing config file
   * If it does match and it has arguments in it, it will return the arguments
   *
   * @param  array  $configRoute route from config file
   * @param  array  $route    route requested
   * @return array|boolean Array if a route was found, false otherwise
   */
  private static function analyzeSplitRoutes(array $configRoute, array $route)
  {
    $return = [];
    for ($i = 0; $i < count($configRoute); ++$i) {
      if ($configRoute[$i] === $route[$i]) {
        // so far we match. Keep going.
        continue;
      } else if (strpos($configRoute[$i], '{') !== false) {
        // routing has a parameter in the url
        $return[$configRoute[$i]] = $route[$i];
        continue;
      } else {
        return false;
      }
    }
    return $return;
  }

  /**
   * Checks to see if the page is restricted or not, and whether the user can view it.
   *
   * @param  array $routeConfig
   * @return boolean
   */
  private static function userCanAccessPage(array $routeConfig)
  {
    if (isset($routeConfig['visibleTo'])) {

      if (Gatekeeper::checkPermissions(key($routeConfig['visibleTo']), Gatekeeper::LOG_IN_LEVEL_ALL, current($routeConfig['visibleTo']))) {
        return true;
      } else if (!Gatekeeper::isLoggedIn() && PHP_SAPI !== 'cli') {
        Gatekeeper::logIn(str_replace('http:', 'https:', $_SERVER['HTTP_REFERER']));
        return false;
      } else if (PHP_SAPI === 'cli' && \Config::isBeta()) {
        // cron job, so users can always access pages
        return true;
      }
      return false;
    }
    return true;
  }


}