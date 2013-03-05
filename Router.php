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
class Router
{
  /**
   * Header response code to use if the route isn't found
   * @var integer
   */
  private static $routeNotFoundCode = 404;

  /**
   * Handles the requested route and calls the respective controller<p/>
   * VisibleTo Options:
   * <code>
   *   array[0] = application name
   *   array[1] = permissions array|string
   *   array[2] = login level
   * </code>
   *
   * Example visibleTo:
   * <code>
   *   array('template', array('callbacks' => array('isAdministrator')), Gatekeeper::LOG_IN_LEVEL_ALL);
   * </code>
   *
   * Example routingConfig:
   * <code>
   *   array(
   *     'index' => array(
   *         'route' => '/',
   *         'handler' => '\Gustavus\Concourse\Test\RouterTestController:index',
   *     ),
   *     'indexTwo' => array(
   *         'route' => '/indexTwo/{id}',
   *         'handler' => '\Gustavus\Concourse\Test\RouterTestController:indexTwo',
   *         'visibleTo' => array('template', array('admin'))
   *     ),
   *     'indexTwoItem' => array(
   *         'route' => '/indexTwo/item/{id=\d+}',
   *         'handler' => '\Gustavus\Concourse\Test\RouterTestController:showItem',
   *         'visibleTo' => array('template', array('admin'))
   *     ),
   *   );
   * </code>
   *
   * @param  array|string  $routingConfig Array or path to an array of configurations. Keyed by alias values are arrays with options of route, handler, and visibleTo.
   * @param  string $route The path from the application's root that the user is requesting
   * @return string
   */
  public static function handleRequest($routingConfig, $route)
  {
    TemplatePageRequest::initExtremeMaintenance();
    if (!is_array($routingConfig)) {
      $routingConfig = include($routingConfig);
    }
    if (strpos($route, '/') !== 0) {
      // all of the routingConfig indexes should be from the application root
      $route = '/' . $route;
    }

    if (($foundRoute = Router::findRoute($routingConfig, $route)) !== false) {
      // could potentially be a more advanced route
      return Router::runHandler($routingConfig[key($foundRoute)], current($foundRoute));
    } else {
      // route not found
      return Router::handleRouteNotFound();
    }
  }

  /**
   * Handles what to do if the route cannot be found.
   *   It will show the errorPage with either of status of 404 normally, or 400 if a regex failed.
   *
   * @return void
   */
  private static function handleRouteNotFound()
  {
    if (self::$routeNotFoundCode === 400) {
      $header = 'HTTP/1.0 400 Bad Request';
      $_SERVER['REDIRECT_STATUS'] = 400;
    } else {
      $header = 'HTTP/1.0 404 Not Found';
      $_SERVER['REDIRECT_STATUS'] = 404;
    }

    // we don't want the auxbox to be displayed
    $GLOBALS['templatePreferences']['auxBox'] = false;
    header($header);
    ob_start();

    //$_SERVER['REDIRECT_STATUS'] = 404;
    $_SERVER['REDIRECT_URL']    = false;
    include '/cis/www/errorPages/error.php';

    ob_end_flush();
    exit;
  }

  /**
   * Runs the handler set in $routeConfig
   *
   * @param  array $routeConfig
   * @param  array $args  arguments to pass onto the controller
   * @return string false if user can't access page. String otherwise
   */
  protected static function runHandler(array $routeConfig, array $args = array())
  {
    if (!Router::userCanAccessPage($routeConfig)) {
      header('HTTP/1.0 403 Forbidden');
      ob_start();

      $_SERVER['REDIRECT_STATUS'] = 403;
      $_SERVER['REDIRECT_URL'] = $_SERVER['HTTP_REFERER'];
      include_once('/cis/www/errorPages/error.php');

      ob_end_flush();
      exit;
    }

    $handler = explode(':', $routeConfig['handler']);
    if (empty($args)) {
      return call_user_func(array(new $handler[0], $handler[1]));
    }
    // pass the associative array created by analyzeSplitRoutes.
    // This requires the handlers to take in one argument
    return call_user_func(array(new $handler[0], $handler[1]), $args);

  }

  /**
   * Finds advanced routing
   *
   * @param  array $routes
   * @param  string $route
   * @return array|boolean Array keyed by route value of analyzed result if a route was found, false otherwise
   */
  private static function findRoute(array $routes, $route)
  {
    // first lets split the route up
    $splitRoute = explode('/', trim($route, '/'));

    foreach ($routes as $key => $value) {
      $splitKey = explode('/', trim($value['route'], '/'));
      //$splitKey = explode('/', trim($key, '/'));
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
        if (empty($route[$i]) && !is_numeric($route[$i])) {
          // we don't want to keep going
          return false;
        } else {
          $trimmedConfigRoute = trim($configRoute[$i], '{}');
          if (($key = Router::checkRouteRegex($trimmedConfigRoute, $route[$i])) !== false) {
            // it matches
            $return[$key] = $route[$i];
            continue;
          } else {
            // no match.
            // set the response code in case we don't find a route later on.
            self::$routeNotFoundCode = 400;
            return false;
          }
        }
      } else {
        return false;
      }
    }
    return $return;
  }

  /**
   * Checks to see if the route has a regex associated with it or not. If it does, it will check the requested route against it.
   *
   * @param  string $configRoute The piece of the configuration route in question
   * @param  string $currRoute   The piece of the requested route
   * @return boolean|string      False if the route doesn't match the regex. The config route without the regex attached if it is valid
   */
  private static function checkRouteRegex($configRoute, $currRoute)
  {
    if (($pos = strpos($configRoute, '=')) !== false) {
      $regex = substr($configRoute, $pos + 1);
      if (preg_match("`^{$regex}$`", $currRoute) === 1) {
        return substr($configRoute, 0, $pos);
      }
      return false;
    }
    return $configRoute;
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
      $applicationName = isset($routeConfig['visibleTo'][0]) ? $routeConfig['visibleTo'][0] : '';
      $permissions     = isset($routeConfig['visibleTo'][1]) ? $routeConfig['visibleTo'][1] : [];
      $loginLevel      = isset($routeConfig['visibleTo'][2]) ? $routeConfig['visibleTo'][2] : Gatekeeper::LOG_IN_LEVEL_ALL;

      if (is_string($permissions)) {
        $permissions = [$permissions];
      }

      if (Gatekeeper::checkPermissions($applicationName, $loginLevel, $permissions)) {
        return true;
      } else if (!Gatekeeper::isLoggedIn() && PHP_SAPI !== 'cli') {
        Gatekeeper::logIn('https://' . $_SERVER['SERVER_NAME'] . $_SERVER['REQUEST_URI']);
        return false;
      }
      return false;
    }
    return true;
  }
}