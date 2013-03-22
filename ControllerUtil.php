<?php
/**
 * @package Concourse
 * @author Billy Visto
 */

namespace Gustavus\Concourse;

/**
 * Utilities for the controller class
 *
 * @package  Concourse
 * @author  Billy Visto
 */
class ControllerUtil
{
  /**
   * Redirects to the specified path
   *
   * @param  string $path path to redirect to.
   * @return void
   */
  public static function redirect($path = '/')
  {
    $_POST = null;
    header('Location: ' . $path, true, 303);
    exit;
  }
}