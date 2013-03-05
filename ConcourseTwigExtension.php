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
   * @return string
   */
  public function buildUrl($alias, array $parameters = array())
  {
    return $this->controller->buildUrl($alias, $parameters);
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