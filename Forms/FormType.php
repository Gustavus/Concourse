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
class FormType extends FormItem
{
  protected $items = [];
  protected $itemMap = [];

  /**
   * Constructs and initializes a new LocationTypeInput instance using the specified name and type.
   * If type is not defined, it defaults to "container."
   *
   * @param string $name
   *  The name to use for this element.
   *
   * @throws \InvalidArgumentException
   *  If $name or $type is null, empty, not a string or not well-formed.
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
            $callable = $property;
            $property = $key;
          }
          $getData = 'get' . ucfirst($property);

          $value   = $data->$getData();
          if (isset($callable) && !empty($value)) {
            if (count($callable) > 1) {
              $value = call_user_func([$value, $callable[0]], $callable[1]);
            } else {
              $value = call_user_func([$value, $callable[0]]);
            }
          }
          $valid   &= $this->itemMap[$property]->populateItem($value);
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
   * @param  mixed &$data Object to populate data to
   * @return boolean
   */
  public function populateData(&$data)
  {
    foreach ($this->getItemMapping() as $objType => $properties) {
      if ($data instanceof $objType) {
        foreach ($properties as $key => $property) {
          if (is_array($property)) {
            $property = $key;
          }
          $value   = $this->itemMap[$property]->getValue();

          $setProp = 'set' . ucfirst($property);
          $data->$setProp($value);
        }
        return true;
      }
    }
    return false;
  }
}