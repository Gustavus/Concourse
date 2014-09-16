<?php
/**
 * @package Concourse
 * @author Billy Visto
 */

namespace Gustavus\Concourse;

// we don't want the template to start when we require it.
// This will still set up debugging and extreme maintenance.
if (!defined('GUSTAVUS_START_TEMPLATE')) {
  define('GUSTAVUS_START_TEMPLATE', false);
}

require_once 'template/request.class.php';
require_once 'gatekeeper/gatekeeper.class.php';

use Gustavus\Gatekeeper\Gatekeeper,
  Template\PageActions,
  Gustavus\Utility\File,
  Gustavus\Utility\PageUtil;

/**
 * Manages sending people to the requested page. Checks to see if the user has access to it first.
 *
 * @package  Concourse
 * @author  Billy Visto
 */
class Router
{
  /**
   * The alias of the found route
   *
   * @var string
   */
  public static $routeAlias;

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
   *         'visibleTo' => array('template', array('admin')),
   *         'breadCrumbs' => [['url' => 'Some Url', 'text' => 'text']],
   *     ),
   *     'indexTwoItem' => array(
   *         'route' => '/indexTwo/item/{id=\d+}',
   *         'handler' => '\Gustavus\Concourse\Test\RouterTestController:showItem',
   *         'visibleTo' => array('template', array('admin'))
   *         'breadCrumbs' => [['alias' => 'index', 'text' => 'text']],
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
    // Handle users logging in/out and requesting impersonation.
    PageActions::handleActions();

    if (!is_array($routingConfig)) {
      $routingConfig = include($routingConfig);
    }
    if (strpos($route, '/') !== 0) {
      // all of the routingConfig indexes should be from the application root
      $route = '/' . $route;
    }

    if (($foundRoute = Router::findRoute($routingConfig, $route)) !== false) {
      // could potentially be a more advanced route
      return Router::runHandler(key($foundRoute), $routingConfig[key($foundRoute)], current($foundRoute));
    } else {
      // route not found
      return Router::handleRouteNotFound();
    }
  }

  /**
   * Handles what to do if the route cannot be found.
   *   It will show the errorPage with either of status of 404 normally, or 400 if a regex failed.
   *
   * @return string Contents of the output buffer
   */
  private static function handleRouteNotFound()
  {
    if (self::$routeNotFoundCode === 400) {
      return PageUtil::renderBadRequest(true);
    }
    return PageUtil::renderPageNotFound(true);
  }

  /**
   * Runs the handler set in $routeConfig.
   *   It tries to serve the file specified in $routeConfig['route'] if no handler is specified.
   *
   * @param  string $alias the alias we are running the handler for
   * @param  array  $routeConfig
   * @param  array  $args  arguments to pass onto the controller
   * @return string
   */
  protected static function runHandler($alias, array $routeConfig, array $args = array())
  {
    if (!Router::userCanAccessPage($routeConfig)) {
      return PageUtil::renderAccessDenied(true);
    }

    if (!isset($routeConfig['handler'])) {
      // let's try to serve the file since the handler doesn't exist.
      $path = self::createFilePathFromRoute($routeConfig['route'], $args);

      (new File($path))->serve();
      exit;
    }

    $handler = explode(':', $routeConfig['handler']);
    if (empty($args)) {
      return call_user_func(array(new $handler[0]($alias), $handler[1]));
    }
    // pass the associative array created by analyzeSplitRoutes.
    // This requires the handlers to take in one argument
    return call_user_func(array(new $handler[0]($alias), $handler[1]), $args);

  }

  /**
   * Creates a file path from the route and the arguments for the route
   *
   * @param  string $route
   * @param  array  $args  arguments that fill in dynamic pieces of the route
   * @return string
   */
  private static function createFilePathFromRoute($route, $args)
  {
    if (strpos($route, '/') === 0) {
      $route = substr($route, 1);
    }

    if (strpos($route, '{') !== false) {
      // we need to fill the url with parameters
      foreach ($args as $key => $param) {
        $route = preg_replace("`{{$key}.*?}`", $param, $route);
      }
    }

    return $route;
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
      // make sure this piece has a route.
      if (isset($value['route'])) {
        $splitConfigRoute = explode('/', trim($value['route'], '/'));
        $routeContainsWildCard = (strpos($value['route'], '=*') !== false);

        if (count($splitConfigRoute) === count($splitRoute) || $routeContainsWildCard) {
          // we might have a match. Let's look to see how close they match
          $analyzeResult = Router::analyzeSplitRoutes($splitConfigRoute, $splitRoute);
          if ($analyzeResult !== false) {
            // we have a match!
            return [$key => $analyzeResult];
          }
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
          $trimmedConfigRoute = preg_replace('`(?:^\{|\}$)`', '', $configRoute[$i]);
          if (($wildCardPos = strpos($trimmedConfigRoute, '=*')) !== false) {
            // we have a wildCard.
            // Everything from here on will match the current argument
            $caughtRoutes = array_slice($route, $i);
            $return[substr($trimmedConfigRoute, 0, $wildCardPos)] = implode('/', $caughtRoutes);
            return $return;
          }
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