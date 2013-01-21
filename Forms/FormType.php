<?php
/**
 * @package Concourse
 * @subpackage Forms
 * @author  Billy Visto
 */

namespace Gustavus\Concourse\Forms;

use Gustavus\FormBuilder\FormItem;

/**
 * Creates a formType for forms to extend
 *
 * @package Concourse
 * @subpackage Forms
 * @author  Billy Visto
 */
abstract class FormType extends FormItem
{
  protected $items = [];
  protected $itemMap = [];

  /**
   * Constructs and initializes a new LocationTypeInput instance using the specified name and type.
   * If type is not defined, it defaults to "container."
   *
   * @param string $name
   *  The name to use for this element.
   */
  public function __construct($name)
  {
    parent::__construct($name, 'container');

    $this->setUpItemMapping();
  }

  /**
   * Sets up item mapping based off of the formItems added to the itemMap
   *
   * @return  void
   */
  private function setUpItemMapping()
  {
    foreach ($this->itemMap as $item) {
      $this->items[] = $item;
      $item->parent  = $this;
    }
  }

  /**
   * {@inheritDoc}
   */
  public function getChildren()
  {
    return $this->items;
  }

  /**
   * {@inheritDoc}
   */
  public function getValue()
  {
    $values = [];

    foreach ($this->items as $item) {
      $name = $item->getName();

      if (array_key_exists($name, $values)) {
        if (is_array($values[$name]) && array_key_exists(0, $values[$name])) {
          $values[$name][] = $item->getValue();
        } else {
          $values[$name] = [$values[$name], $item->getValue()];
        }
      } else {
        $values[$name] = $item->getValue();
      }
    }

    return $values;
  }

  /**
   * {@inheritDoc}
   */
  public function clearValue()
  {
    if (isset($this->items)) {
      foreach ($this->items as $item) {
        $item->clearValue();
      }
    }

    return $this;
  }

  /**
   * {@inheritDoc}
   */
  public function validateData(&$data)
  {
    if (empty($data) || !is_array($data)) {
      return false; // Not location type data.
    }

    $valid = true;

    foreach ($this->itemMap as $name => $item) {
      $valid &= $item->validateData(isset($data[$name]) ? $data[$name] : null);
    }

    return (bool) $valid;
  }

  /**
   * {@inheritDoc}
   */
  public function hasValidData()
  {
    $valid = true;

    foreach ($this->items as $item) {
      $valid &= $item->hasValidData();
    }

    // Return result...
    $this->setAttribute('analyzed', true);
    return (bool) $valid;
  }

  /**
   * @throws  \BadFunctionCallException If the callable is not callable
   *
   * {@inheritDoc}
   */
  public function populateItem(&$data)
  {
    if (empty($data)) {
      return false;
    }

    // Array
    if (is_array($data)) {
      $valid = true;

      foreach ($this->itemMap as $name => $item) {
        $value = isset($data[$name]) ? $data[$name] : null;
        $valid &= $item->populateItem($value);
      }

      return (bool) $valid;
    }

    foreach ($this->getItemMapping() as $objectType => $properties) {
      if ($data instanceof $objectType) {
        $valid = true;
        foreach ($properties as $key => $property) {
          if (is_array($property)) {
            $callables = $property;
            $property = $key;
          }

          $getData = 'get' . ucfirst($property);
          if (is_callable([$data, $getData])) {
            $value = $data->$getData();
          }
          if (isset($callables[0])) {
            // the get calls will be the first index
            $callables = $callables[0];

            // if (is_array(current($callables)) && !is_array(current(current($callables)))) {
            //   $callables = [$callables];
            // }
            // if (!is_array(current($callables))) {
            //   if (!current($callables)) {
            //     // the app has specified to not call a callable
            //     continue;
            //   }
            //   // function without any arguments, we need to wrap this in an array
            //   $callables = [$callables];
            // }
            // for ($i = 0; $i < count($callables); ++$i) {
            //   $callable = $callables[$i];
            $i = 0;
            foreach ($callables as $callableKey => $callable) {
              if (is_string($callableKey)) {
                // we have a callable with parameters
                $arguments = $callable;
                $callable  = $callableKey;
              }
              if (!$callable) {
                // no callable to try to call
                continue;
              }
              if (!isset($value) && $i === 0) {
                // first time through. This will be called on the data
                $value = $data;
              }
              if (!is_callable([$value, $callable])) {
                throw new \BadFunctionCallException('The callable "' . $callable . '" could not be called on ' . getType($value));
              }
              if (isset($arguments)) {
                $value = call_user_func([$value, $callable], $arguments);
                unset($arguments);
              } else {
                $value = call_user_func([$value, $callable]);
              }
              ++$i;
            }
            unset($callables);
          } else if (!isset($value)) {
            throw new \UnexpectedValueException('The property "' . $property . '" does not appear to have a getter for ' . $objectType);
          }
          $valid   &= $this->itemMap[$property]->populateItem($value);
          unset($value);
        }
        return (bool) $valid;
      }
    }

    $valid = true;
    $value = null;

    // Wrong type...
    return false;
  }

  /**
   * Populates object with form data
   *
   * @throws  \BadFunctionCallException If the callable is not callable
   *
   * @param  mixed &$data Object to populate data to
   * @return boolean
   */
  public function populateData(&$data)
  {
    foreach ($this->getItemMapping() as $objType => $properties) {
      if ($data instanceof $objType) {
        foreach ($properties as $key => $property) {
          if (is_array($property)) {
            $callables = $property;
            $property  = $key;
          }
          if ($property == 'units') {
            var_dump('here', $property, $key);
            exit;
          }
          if (isset($callables[1])) {
            // the set calls will be the second index
            $callables = $callables[1];

            $getData = 'get' . ucfirst($property);
            if (is_callable([$data, $getData])) {
              $value   = $data->$getData();
            }
            if (empty($value)) {
              // property doesn't exist. Must be a new one. Let's try setting it.
              $setData = 'set' . ucfirst($property);
              if (is_callable([$data, $setData])) {
                $data->$setData($this->itemMap[$property]->getValue());
                continue;
              }
            }

            // we know this is set because property got renamed to callables

            // if (is_array(current($callables)) && !is_array(current(current($callables)))) {
            //   // function with arguments, but without an array wrapping the functions.
            //   $callables = [$callables];
            // }
            // if (!is_array(current($callables))) {
            //   // function without any arguments, we need to wrap this in an array
            //   $callables = [$callables];
            // }
            // for ($i = 0; $i < count($callables); ++$i) {
            //   $callable = $callables[$i];
            $i = 0;
            foreach ($callables as $callableKey => $callable) {
              if (is_string($callableKey)) {
                // we have a callable with parameters
                $arguments = $callable;
                $callable  = $callableKey;
              }
              if (!$callable) {
                // no callable to try to call
                continue;
              }
              if (!isset($value) && $i === 0) {
                // first time through. This will be called on the data
                $value = $data;
              }

              if (!is_callable([$value, $callable])) {
                // check to see if we can fallback to the data
                if (is_callable([$data, $callable])) {
                  $value = $data;
                } else {
                  throw new \BadFunctionCallException('The callable "' . $callable . '" could not be called' );
                }
              }
              if (isset($arguments)) {
                $value = call_user_func([$value, $callable], $arguments);
              } else {
                if ($i + 1 === count($callables)) {
                  // this is the last callable to be called. These functions should accept one parameter.
                  call_user_func([$value, $callable], $this->itemMap[$property]->getValue());
                  continue;
                }
                $value = call_user_func([$value, $callable]);
              }
            }
            unset($callables);
          } else {
            $setProp = 'set' . ucfirst($property);
            if (!is_callable([$data, $setProp])) {
              throw new \BadFunctionCallException('The property "' . $property . '" does not have a callable "' . $setProp . '" function' );
            }
            $data->$setProp($this->itemMap[$property]->getValue());
          }
        }
        return true;
      }
    }
    return false;
  }

  /**
   * Gets the mapping information specified by the itemMapping array<p/>
   * Returns array with key of objectType and value as an array of properties to map. <p/>
   *   Properties can request callable functions as their value.
   *     These callable functions must be contained in an array with
   *     the first index being the the get methods and the second
   *     being the set methods
   *
   * @return array Array of item mapping
   */
  abstract protected function getItemMapping();
}