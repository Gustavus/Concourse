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
 * RoutingUtil tests
 *
 * @package Concourse
 * @subpackage Test
 * @author  Billy Visto
 */
class RoutingUtilTest extends Test
{
  /**
   * Base Directory for urls
   */
  const BASE_DIR = '/Gustavus/Concourse/ControllerTest/';

  /**
   * @var Array routing configuration
   */
  private $routingConfig = [
    'index' => [
      'route'   => '/',
      'handler' => '\Gustavus\Concourse\Test\RouterTestController:index',
    ],
    'indexTwo' => [
      'route'       => '/indexTwo/{id}',
      'handler'     => '\Gustavus\Concourse\Test\RouterTestController:indexTwo',
      'breadCrumbs' => [['url' => 'some url', 'text' => 'some text']],
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
    $_SERVER['SCRIPT_NAME'] = self::BASE_DIR . 'index.php';
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
    $this->assertSame($expected, RoutingUtil::buildUrl($this->routingConfig, 'index', array(), '/'));
  }

  /**
   * @test
   */
  public function buildUrlEmptyBaseDir()
  {
    $expected = self::BASE_DIR;
    $this->assertSame($expected, RoutingUtil::buildUrl($this->routingConfig, 'index'));
  }

  /**
   * @test
   */
  public function buildUrlFullUrl()
  {
    $_SERVER['SCRIPT_NAME'] = '/index.php';
    $_SERVER['HTTP_HOST'] = 'gustavus.edu';
    $expected = 'https://gustavus.edu/indexTwo/3';
    $this->assertSame($expected, RoutingUtil::buildUrl($this->routingConfig, 'indexTwo', array('id' => 3), '', true));
  }

  /**
   * @test
   */
  public function buildUrlParam()
  {
    $expected = '/arst/indexTwo/2';
    $this->assertSame($expected, RoutingUtil::buildUrl($this->routingConfig, 'indexTwo', ['id' => 2], '/arst'));
  }

  /**
   * @test
   */
  public function buildUrlEncodedParam()
  {
    $expected = '/arst/indexTwo/file%25arst';
    $this->assertSame($expected, RoutingUtil::buildUrl($this->routingConfig, 'indexTwo', ['id' => 'file%arst'], '/arst'));
  }

  /**
   * @test
   */
  public function buildUrlParams()
  {
    $expected = '/arst/indexTwo/2/hello';
    $this->assertSame($expected, RoutingUtil::buildUrl($this->routingConfig, 'indexTwoKey', ['id' => 2, 'key' => 'hello'], '/arst/'));
  }

  /**
   * @test
   * @expectedException OutOfBoundsException
   */
  public function buildUrlParamNotFound()
  {
    $actual = RoutingUtil::buildUrl($this->routingConfig, 'indexT', ['id' => 2]);
    $this->assertNull($actual);
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
   * @expectedException OutOfBoundsException
   */
  public function forwardNotFound()
  {
    $actual = RoutingUtil::forward($this->routingConfig, 'indexa');
    $this->assertNull($actual);
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
  public function forwardWithHandler()
  {
    $actual = RoutingUtil::forward($this->routingConfig, '\Gustavus\Concourse\Test\RouterTestController:indexTwo', ['id' => 23]);
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

  /**
   * @test
   */
  public function getBreadCrumbs()
  {
    $actual = RoutingUtil::getBreadCrumbs($this->routingConfig, 'indexTwo');
    $this->assertSame($this->routingConfig['indexTwo']['breadCrumbs'], $actual);
  }

  /**
   * @test
   */
  public function getBreadCrumbsNoneDefined()
  {
    $actual = RoutingUtil::getBreadCrumbs($this->routingConfig, 'indexTwoKey');
    $this->assertSame([], $actual);
  }

  /**
   * @test
   * @expectedException OutOfBoundsException
   */
  public function getBreadCrumbsAliasNotFound()
  {
    $actual = RoutingUtil::getBreadCrumbs($this->routingConfig, 'indexTwoKeys');
    // exception
  }

  /**
   * @test
   */
  public function findForwardingHandlerAlias()
  {
    $actual = $this->call('\Gustavus\Concourse\RoutingUtil', 'findForwardingHandlerAlias', array($this->routingConfig, '\Gustavus\Concourse\Test\RouterTestController:indexTwo'));
    $this->assertSame('indexTwo', $actual);
  }

  /**
   * @test
   */
  public function findForwardingHandlerAliasNotFound()
  {
    $expected = ['handler' => '\Gustavus\Concourse\Test\RouterTestController:indexTwoarst'];
    $actual = $this->call('\Gustavus\Concourse\RoutingUtil', 'findForwardingHandlerAlias', array($this->routingConfig, '\Gustavus\Concourse\Test\RouterTestController:indexTwoarst'));
    $this->assertSame($expected, $actual);
  }
}
