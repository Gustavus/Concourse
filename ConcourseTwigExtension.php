<?php
/**
 * @package Concourse
 * @author  Billy Visto
 */

namespace Gustavus\Concourse;

use Twig_Extension,
  Twig_SimpleFunction;

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
   *   This takes in the same parameters as buildUrl in $this->controller
   *
   * @return string
   */
  public function buildUrl()
  {
    return call_user_func_array([$this->controller, 'buildUrl'], func_get_args());
  }

  /**
   * For \Twig_ExtensionInterface
   *
   * @return array
   */
  public function getFunctions()
  {
    return array(
      new Twig_SimpleFunction('buildUrl', [$this, 'buildUrl']),
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