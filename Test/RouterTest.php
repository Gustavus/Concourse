<?php
/**
 * @package Concourse
 * @subpackage Test
 * @author  Billy Visto
 */

namespace Gustavus\Concourse;

require_once 'gatekeeper/gatekeeper.class.php';

use Gustavus\Test\Test,
  Gustavus\Test\TestObject,
  Gustavus\Concourse\Router,
  Gustavus\Gatekeeper\Gatekeeper;

/**
 * Router tests
 *
 * @package Concourse
 * @subpackage Test
 * @author  Billy Visto
 */
class RouterTest extends Test
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
   * Override tokens
   * @var array
   */
  private $overrides = array();

  /**
   * Place to store headers we are trying to set
   * @var array
   */
  public $headers = array();

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
    unset($this->overrides);
  }

  /**
   * Sets up overriding header()
   *
   * @return void
   */
  private function overrideHeader()
  {
    $self = $this;
    $this->overrides['header'] = override_function('header', function ($header) use (&$self) {
      $self->headers[] = $header;
    });
  }


  /**
   * @test
   */
  public function handleRequest()
  {
    $actual = Router::handleRequest($this->routingConfig, '');
    $this->assertSame('RouterTestController index()', $actual);
  }

  /**
   * @test
   */
  public function handleRequestFileName()
  {
    $actual = Router::handleRequest('/cis/lib/Gustavus/Concourse/Test/routing.php', '');
    $this->assertSame('RouterTestController index()', $actual);
  }

  /**
   * @test
   */
  public function handleRequestAdvanced()
  {
    $actual = Router::handleRequest($this->routingConfig, '/indexTwo/23');
    $this->assertSame('RouterTestController indexTwo(23)', $actual);
  }

  /**
   * @test
   */
  public function handleRequestAdvancedTwoParams()
  {
    $actual = Router::handleRequest($this->routingConfig, '/indexTwo/23/arst');
    $this->assertSame('RouterTestController indexThree(23, arst)', $actual);
  }

  /**
   * test
   */
  public function handleRequestNotFound()
  {
    $_SERVER['SERVER_NAME'] = 'testing';
    $_SERVER['REQUEST_URI'] = 'testing';
    $_SERVER['REMOTE_ADDR'] = 'testing';
    $this->overrideHeader();
    $actual = Router::handleRequest($this->routingConfig, '/indexTwo/23/25/arst');
    $this->assertNotEmpty($actual);
    $this->assertContains('404', $actual);
    ob_start();
  }

  /**
   * @test
   */
  public function handleRequestSecure()
  {
    $this->authenticate('bvisto');
    $this->routingConfig['/indexTwo/{id}']['visibleTo'] = array('ProjectName', [Gatekeeper::PERMISSION_ALL]);
    $actual = Router::handleRequest($this->routingConfig, '/indexTwo/23');
    $this->assertSame('RouterTestController indexTwo(23)', $actual);
    $this->unAuthenticate();
  }

  /**
   * @test
   * @dataProvider findRouteData
   */
  public function findRoute($routes, $route, $expected)
  {
    $actual = $this->call('\Gustavus\Concourse\Router', 'findRoute', array($routes, $route));
    $this->assertSame($expected, $actual);
  }

  /**
   * FindRoute data
   * @return array
   */
  public function findRouteData()
  {
    return array(
      array(['index' => ['route' => '/'], 'indexTwo' => ['route' => '/indexTwo/id'], 'indexTwoId' => ['route' => '/indexTwo/id/{id=\d+}']], '/indexTwo/id/2.5', false),
      array(['index' => ['route' => '/'], 'indexTwo' => ['route' =>  '/indexTwo/{id}']], '/', ['index' => []]),
      array(['index' => ['route' => '/'], 'indexTwo' => ['route' => '/indexTwo/{id}']], '/indexTwo/23', ['indexTwo' => ['id' => '23']]),
      array(['index' => ['route' => '/'], 'indexTwo' => ['route' => '/indexTwo/id']], '/indexTwo/id', ['indexTwo' => []]),
      array(['index' => ['route' => '/'], 'indexTwoId' => ['route' => '/indexTwo/id']], '/indexTwo/id/23', false),
      array(['index' => ['route' => '/'], 'indexTwo' => ['route' => '/indexTwo/id'], 'indexTwoId' => ['route' => '/indexTwo/id/{id}']], '/indexTwo/id/23', ['indexTwoId' => ['id' => '23']]),
      array(['index' => ['route' => '/'], 'indexTwo' => ['route' => '/indexTwo/id'], 'indexTwoId' => ['route' => '/indexTwo/id/id']], '/indexTwo/id/23', false),
    );
  }

  /**
   * @test
   * @dataProvider analyzeSplitRoutesData
   */
  public function analyzeSplitRoutes($configRoute, $route, $expected)
  {
    $actual = $this->call('\Gustavus\Concourse\Router', 'analyzeSplitRoutes', array($configRoute, $route));
    $this->assertSame($expected, $actual);
  }

  /**
   * AnalyzeSplitRoutesData
   * @return  array
   */
  public function analyzeSplitRoutesData()
  {
    return array(
      array(['{id}'], [''], false),
      array(['{id}'], ['a'], ['id' => 'a']),
      array(['menu'], ['menu'], []),
      array([''], [''], []),
      array(['indexTwo', '{id}'], ['indexTwo', '23'], ['id' => '23']),
      array(['indexTwo', 'id', '{id2}'], ['indexTwo', '23', '25'], false),
      array(['indexTwo', '{id}', '{id2}'], ['indexTwo', '23', '25'], ['id' => '23', 'id2' => '25']),
      array(['indexTwo', '{id}', 'id2'], ['indexTwo', '23', '25'], false),
      array(['indexTwo', '{id}', 'id2'], ['indexTwo', '23', 'id2'], ['id' => '23']),
      array(['indexTwo', '{id}', 'id2'], ['indexTwo', '23', 'id2'], ['id' => '23']),
      array(['indexTwo', '{id=\d+}', 'id2'], ['indexTwo', '23', 'id2'], ['id' => '23']),
      array(['indexTwo', '{id=\d+}', 'id2'], ['indexTwo', '2.5', 'id2'], false),
      array(['indexTwo', '{id=\d+}', 'id2'], ['indexTwo', 'hello', 'id2'], false),
    );
  }

  /**
   * @test
   * @dataProvider checkRouteRegexData
   */
  public function checkRouteRegex($expected, $configRoute, $route)
  {
    $this->assertSame($expected, $this->call('\Gustavus\Concourse\Router', 'checkRouteRegex', array($configRoute, $route)));
  }

  /**
   * checkRouteRegexData
   * @return array
   */
  public function checkRouteRegexData()
  {
    return [
      ['id', 'id=\d+', '23'],
      [false, 'id=\d+', '2.5'],
      [false, 'id=\d+', 'hello'],
    ];
  }

  /**
   * @test
   */
  public function findRouteCheckingResponseCode()
  {
    $configRoute = ['index' => ['route' => '/'], 'indexTwo' => ['route' => '/indexTwo/{id=\d+}'], 'indexTwoTest' => ['route' => '/indexTwo/test/{id}']];
    $actual = $this->call('\Gustavus\Concourse\Router', 'findRoute', array($configRoute, '/indexTwo/help'));
    $this->assertFalse($actual);
    $this->assertSame(400, $this->get('\Gustavus\Concourse\Router', 'routeNotFoundCode'));

    // reset this
    $this->set('\Gustavus\Concourse\Router', 'routeNotFoundCode', 404);

    $configRoute = ['index' => ['route' => '/'], 'indexTwo' => ['route' => '/indexTwo/{id=\w+}'], 'indexTwoId' => ['route' => '/indexTwo/{id=\d+}']];
    $actual = $this->call('\Gustavus\Concourse\Router', 'findRoute', array($configRoute, '/indexTwo/help'));
    $this->assertSame(['indexTwo' => ['id' => 'help']], $actual);
    $this->assertSame(404, $this->get('\Gustavus\Concourse\Router', 'routeNotFoundCode'));
  }

  /**
   * @test
   */
  public function userCanAccessPage()
  {
    $this->routingConfig['/indexTwo/{id}']['visibleTo'] = [
        'studentOrgs',
        [Gatekeeper::PERMISSION_ANONYMOUS],
    ];

    $this->authenticate('bvisto');
    $actual = $this->call('\Gustavus\Concourse\Router', 'userCanAccessPage', array($this->routingConfig['/indexTwo/{id}']));
    $this->assertTrue($actual);
    $this->unAuthenticate();
  }

  /**
   * @test
   */
  public function userCanAccessPageStringPermissions()
  {
    $this->routingConfig['/indexTwo/{id}']['visibleTo'] = [
        'studentOrgs',
        Gatekeeper::PERMISSION_ANONYMOUS,
    ];

    $this->authenticate('bvisto');
    $actual = $this->call('\Gustavus\Concourse\Router', 'userCanAccessPage', array($this->routingConfig['/indexTwo/{id}']));
    $this->assertTrue($actual);
    $this->unAuthenticate();
  }

  /**
   * @test
   */
  public function userCanAccessPageLoginLevel()
  {
    $this->routingConfig['/indexTwo/{id}']['visibleTo'] = [
        'studentOrgs',
        [Gatekeeper::PERMISSION_ANONYMOUS],
        'loginLevel' => Gatekeeper::LOG_IN_LEVEL_ALL
    ];

    $this->authenticate('bvisto');
    $actual = $this->call('\Gustavus\Concourse\Router', 'userCanAccessPage', array($this->routingConfig['/indexTwo/{id}']));
    $this->assertTrue($actual);
    $this->unAuthenticate();
  }

  /**
   * @test
   */
  public function userCanAccessPageCallbacks()
  {
    $this->routingConfig['/indexTwo/{id}']['visibleTo'] = [
        'studentOrgs',
        [Gatekeeper::PERMISSION_ANONYMOUS, 'callbacks' => [[$this, 'someCallback']]],
    ];

    $this->authenticate('bvisto');
    $actual = $this->call('\Gustavus\Concourse\Router', 'userCanAccessPage', array($this->routingConfig['/indexTwo/{id}']));
    $this->assertFalse($actual);
    $this->unAuthenticate();
  }

  /**
   * Callback for testing callbacks
   * @return boolean
   */
  public function someCallback()
  {
    return false;
  }

  /**
   * @test
   */
  public function runHandler()
  {
    $actual = $this->call('\Gustavus\Concourse\Router', 'runHandler', ['index', $this->routingConfig['index']]);
    $this->assertSame('RouterTestController index()', $actual);
  }

  /**
   * @test
   */
  public function runHandlerAdvanced()
  {
    $actual = $this->call('\Gustavus\Concourse\Router', 'runHandler', ['indexTwo', $this->routingConfig['indexTwo'], ['id' => 23]]);
    $this->assertSame('RouterTestController indexTwo(23)', $actual);
  }
}
