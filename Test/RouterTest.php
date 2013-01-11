<?php
/**
 * @package Concourse
 * @subpackage Test
 */

namespace Gustavus\Concourse;

require_once 'gatekeeper/gatekeeper.class.php';

use Gustavus\Test\Test,
  Gustavus\Test\TestObject,
  Gustavus\Concourse\Router,
  Gustavus\Gatekeeper\Gatekeeper;

/**
 * @package Concourse
 * @subpackage Test
 */
class RouterTest extends Test
{
  /**
   * @var Array routing configuration
   */
  private $routingConfig = [
    '/' => [
      'handler' => '\Gustavus\Concourse\Test\RouterTestController:index',
    ],
    '/indexTwo/{id}' => [
      'handler' => '\Gustavus\Concourse\Test\RouterTestController:indexTwo',
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
  public function handleRequestNotFound()
  {
    //$actual = Router::handleRequest($this->routingConfig, '/indexTwo/23/25');
    // exception expected. nothing else happens here
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
   */
  public function findAdvancedRoute()
  {
    $actual = $this->call('\Gustavus\Concourse\Router', 'findAdvancedRoute', array(['/', '/indexTwo/{id}'], '/indexTwo/23'));
    $this->assertSame(['/indexTwo/{id}' => ['{id}' => '23']], $actual);

    $actual = $this->call('\Gustavus\Concourse\Router', 'findAdvancedRoute', array(['/', '/indexTwo/id'], '/indexTwo/id'));
    $this->assertSame(['/indexTwo/id' => []], $actual);

    $actual = $this->call('\Gustavus\Concourse\Router', 'findAdvancedRoute', array(['/', '/indexTwo/id'], '/indexTwo/id/23'));
    $this->assertFalse($actual);

    $actual = $this->call('\Gustavus\Concourse\Router', 'findAdvancedRoute', array(['/', '/indexTwo/id', '/indexTwo/id/{id}'], '/indexTwo/id/23'));
    $this->assertSame(['/indexTwo/id/{id}' => ['{id}' => '23']], $actual);

    $actual = $this->call('\Gustavus\Concourse\Router', 'findAdvancedRoute', array(['/', '/indexTwo/id', '/indexTwo/id/id'], '/indexTwo/id/23'));
    $this->assertFalse($actual);
  }

  /**
   * @test
   */
  public function analyzeSplitRoutes()
  {
    $actual = $this->call('\Gustavus\Concourse\Router', 'analyzeSplitRoutes', array(['indexTwo', '{id}'], ['indexTwo', '23']));
    $this->assertSame(['{id}' => '23'], $actual);

    $actual = $this->call('\Gustavus\Concourse\Router', 'analyzeSplitRoutes', array(['indexTwo', 'id', '{id2}'], ['indexTwo', '23', '25']));
    $this->assertFalse($actual);

    $actual = $this->call('\Gustavus\Concourse\Router', 'analyzeSplitRoutes', array(['indexTwo', '{id}', '{id2}'], ['indexTwo', '23', '25']));
    $this->assertSame(['{id}' => '23', '{id2}' => '25'], $actual);

    $actual = $this->call('\Gustavus\Concourse\Router', 'analyzeSplitRoutes', array(['indexTwo', '{id}', 'id2'], ['indexTwo', '23', '25']));
    $this->assertFalse($actual);

    $actual = $this->call('\Gustavus\Concourse\Router', 'analyzeSplitRoutes', array(['indexTwo', '{id}', 'id2'], ['indexTwo', '23', 'id2']));
    $this->assertSame(['{id}' => '23'], $actual);
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

  public function someCallback()
  {
    return false;
  }
}
