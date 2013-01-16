<?php
/**
 * @package  Concourse
 * @subpackage  Forms
 * @author  Billy Visto
 */

namespace Gustavus\Concourse\Forms;

use Gustavus\FormBuilder\FormBuilder;

/**
 * Form class to extend for creating and using forms
 *
 * @package  Concourse
 * @subpackage  Forms
 * @author  Billy Visto
 */
abstract class Form
{
  /**
   * Array of parameters to pass to the formBuilder
   *
   * @var array
   */
  protected static $formProperties = [];

  /**
   * Builds a form using formProperties and returns the build instance
   *
   * @return FormBuilder
   */
  public static function buildForm()
  {
    return FormBuilder::buildFromArray(self::getFormProperties());
  }

  /**
   * Calls getFormProperties on the called class.
   *
   * @throws  \BadFunctionCallException If the extending class doesn't have getFormProperties defined
   *
   * @return array
   */
  protected static function getFormProperties()
  {
    $calledClass = get_called_class();
    if (!is_callable($calledClass, 'getFormProperties')) {
      throw new \BadFunctionCallException("The extending class, $calledClass, must override this function.");
    }
    return $calledClass::getFormProperties();
  }
}