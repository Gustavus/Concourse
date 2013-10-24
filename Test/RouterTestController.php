<?php
/**
 * @package Concourse
 * @subpackage Test
 * @author Billy Visto
 */

namespace Gustavus\Concourse\Test;

/**
 * Controller used for testing the Router
 *
 * @package Concourse
 * @subpackage Test
 * @author Billy Visto
 */
class RouterTestController
{
  /**
   * Test function
   * @return string
   */
  public function index()
  {
    return 'RouterTestController index()';
  }

  /**
   * Test function
   * @return string
   */
  public function indexTwo($testArg)
  {
    return "RouterTestController indexTwo({$testArg['id']})";
  }

  /**
   * Test function
   * @return string
   */
  public function indexThree($testArg)
  {
    return "RouterTestController indexThree({$testArg['id']}, {$testArg['key']})";
  }
}