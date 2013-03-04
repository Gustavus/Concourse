<?php
/**
 * @package Concourse
 * @author  Billy Visto
 */

namespace Gustavus\Concourse;

use Twig_Extension,
  Twig_Function_Method;

/**
 * Twig Extension for Concourse
 *
 * @package Concourse
 * @author  Billy Visto
 */
class ConcourseTwigExtension extends Twig_Extension
{
  /**
   * Full routing configuration or location of configuration file
   * @var array|string
   */
  private static $routeConfig;

  /**
   * Sets routeConfig
   *
   * @param array|string $routeConfig Full routing configuration or path to configuration file
   */
  public function __construct($routeConfig)
  {
    self::$routeConfig = $routeConfig;
  }

  /**
   * Alias to RoutingUtil::buildUrl
   *
   * @param  string $alias       Alias to build url for
   * @param  array  $parameters  Params to put into url
   * @return string
   */
  public function buildUrl($alias, array $parameters = array())
  {
    return RoutingUtil::buildUrl(self::$routeConfig, $alias, $parameters);
  }

  /**
   * For \Twig_ExtensionInterface
   *
   * @return array
   */
  public function getFunctions()
  {
    return array(
      'buildUrl'    => new Twig_Function_Method($this, 'buildUrl'),
    );
  }

  /**
   * For \Twig_ExtensionInterface
   *
   * @return string
   */
  public function getName()
  {
    return 'Concourse';
  }
}