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
   * The controller we are currently working with
   *
   * @var Controller
   */
  private $controller;

  /**
   * Sets the controller
   *
   * @param Controller $controller Concourse Controller
   */
  public function __construct($controller)
  {
    $this->controller = $controller;
  }

  /**
   * Alias to RoutingUtil::buildUrl
   *
   * @param  string $alias       Alias to build url for
   * @param  array  $parameters  Params to put into url
   * @param  string $baseDir     Application's web root
   * @param  boolean $fullUrl   Whether you want the full url or just the relative url
   * @return string
   */
  public function buildUrl($alias, array $parameters = array(), $baseDir = '', $fullUrl = false)
  {
    return $this->controller->buildUrl($alias, $parameters, $baseDir, $fullUrl);
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