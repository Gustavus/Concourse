<?php
/**
 * @package Concourse
 * @subpackage Test
 * @author  Billy Visto
 */

namespace Gustavus\Concourse;

use Gustavus\Test\Test,
  Gustavus\Test\TestObject,
  Gustavus\Concourse\RoutingUtil;

/**
 * @package Concourse
 * @subpackage Test
 * @author  Billy Visto
 */
class RoutingUtilTest extends Test
{
  /**
   * @var Array routing configuration
   */
  private $routingConfig = [
    'index' => [
      'route'   => '/',
      'handler' => '\Gustavus\Concourse\Test\RouterTestController:index',
    ],
    'indexTwo' => [
      'route'   => '/indexTwo/{id}',
      'handler' => '\Gustavus\Concourse\Test\RouterTestController:indexTwo',
    ],
    'indexTwoKey' => [
      'route'   => '/indexTwo/{id}/{key}',
      'handler' => '\Gustavus\Concourse\Test\RouterTestController:indexThree',
    ],
  ];

  /**
   * Sets up the object for every test
   */
  public function setUp()
  {
  }

  /**
   * destroys the object after every test
   */
  public function tearDown()
  {
  }

  /**
   * @test
   */
  public function buildUrl()
  {
    $expected = '/';
    $this->assertSame($expected, RoutingUtil::buildUrl($this->routingConfig, 'index'));
  }

  /**
   * @test
   */
  public function buildUrlParam()
  {
    $expected = '/indexTwo/2';
    $this->assertSame($expected, RoutingUtil::buildUrl($this->routingConfig, 'indexTwo', ['id' => 2]));
  }

  /**
   * @test
   */
  public function buildUrlParams()
  {
    $expected = '/indexTwo/2/hello';
    $this->assertSame($expected, RoutingUtil::buildUrl($this->routingConfig, 'indexTwoKey', ['id' => 2, 'key' => 'hello']));
  }

  /**
   * @test
   */
  public function buildUrlParamNotFound()
  {
    $this->assertSame('/', RoutingUtil::buildUrl($this->routingConfig, 'indexT', ['id' => 2]));
  }

  /**
   * @test
   */
  public function forward()
  {
    $actual = RoutingUtil::forward($this->routingConfig, 'index');
    $this->assertSame('RouterTestController index()', $actual);
  }

  /**
   * @test
   */
  public function forwardAdvanced()
  {
    $actual = RoutingUtil::forward($this->routingConfig, 'indexTwo', ['id' => 23]);
    $this->assertSame('RouterTestController indexTwo(23)', $actual);
  }

  /**
   * @test
   */
  public function forwardAdvancedTwoParams()
  {
    $actual = RoutingUtil::forward($this->routingConfig, 'indexTwoKey', ['id' => 23, 'key' => 'arst']);
    $this->assertSame('RouterTestController indexThree(23, arst)', $actual);
  }

}
