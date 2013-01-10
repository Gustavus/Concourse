<?php
/**
 * @package Concourse
 * @subpackage Test
 */

namespace Gustavus\Concourse;

require_once 'gatekeeper/gatekeeper.class.php';

use Gustavus\Test\Test,
  Gustavus\Test\TestObject,
  Gustavus\Concourse\Controller,
  Gustavus\Concourse\Test\ControllerTestController,
  Gustavus\Gatekeeper\Gatekeeper;

/**
 * @package Concourse
 * @subpackage Test
 */
class ControllerTest extends Test
{
  /**
   * Controller test object
   * @var Controller
   */
  private $controller;

  /**
   * Array of controller construction properties
   * @var array
   */
  private $controllerProperties = array(
    'title'           => 'arst',
    'subTitle'        => 'subtitle',
    'focusBox'        => '<p>FocusBox</p>',
    'stylesheets'     => '<style>some css here</style>',
    'javascripts'     => '<script>Some js here</script>',
    'localNavigation' => [['text' => 'testUrl', 'url' => 'someUrl']],
    'content'         => '<p>some random content</p>',
    'templatePreferences' => ['userBox' => false],
  );

  /**
   * Sets up the object for every test
   */
  public function setUp()
  {
    $this->controller = new TestObject(new ControllerTestController);

    foreach ($this->controllerProperties as $key => $value) {
      $function = 'set' . ucfirst($key);
      if (is_callable(array($this->controller, $function))) {
        $this->controller->$function($value);
      }
    }
  }

  /**
   * destroys the object after every test
   */
  public function tearDown()
  {
    unset($this->controller);
  }

  /**
   * Mocks authentication
   *
   * @param  string $username
   * @return
   */
  private function authenticate($username)
  {
    Gatekeeper::setUsername($username);
    $this->set('\Gustavus\Gatekeeper\Gatekeeper', 'loggedIn', true);
  }

  /**
   * Mocks authentication logged out
   *
   * @return
   */
  private function unAuthenticate()
  {
    $this->set('\Gustavus\Gatekeeper\Gatekeeper', 'user', null);
    $this->set('\Gustavus\Gatekeeper\Gatekeeper', 'loggedIn', false);
  }

  /**
   * @test
   */
  public function getApiKey()
  {
    $this->controller->apiKey = 'arstkeyarst';
    $this->assertSame('arstkeyarst', $this->controller->getApiKey());
  }

  /**
   * @test
   */
  public function getTitle()
  {
    $this->assertSame($this->controllerProperties['title'], $this->controller->getTitle());
  }

  /**
   * @test
   */
  public function getSubTitle()
  {
    $this->assertSame($this->controllerProperties['subTitle'], $this->controller->getSubTitle());
  }

  /**
   * @test
   */
  public function getFocusBox()
  {
    $this->assertSame($this->controllerProperties['focusBox'], $this->controller->getFocusBox());
  }

  /**
   * @test
   */
  public function getStylesheets()
  {
    $this->assertSame($this->controllerProperties['stylesheets'], $this->controller->getStylesheets());
  }

  /**
   * @test
   */
  public function getJavascripts()
  {
    $this->assertSame($this->controllerProperties['javascripts'], $this->controller->getJavascripts());
  }

  /**
   * @test
   */
  public function getContent()
  {
    $this->assertSame($this->controllerProperties['content'], $this->controller->getContent());
  }

  /**
   * @test
   */
  public function getTemplatePreferences()
  {
    $this->assertSame($this->controllerProperties['templatePreferences'], $this->controller->getTemplatePreferences());
  }

  /**
   * @test
   */
  public function addTemplatePreference()
  {
    $addition = ['focusBox' => false];
    $expected = array_merge($this->controllerProperties['templatePreferences'], $addition);
    $this->controller->addTemplatePreferences($addition);
    $this->assertSame($expected, $this->controller->getTemplatePreferences());
  }

  /**
   * @test
   */
  public function isLoggedIn()
  {
    $this->assertFalse($this->controller->isLoggedIn());

    $this->authenticate('bvisto');
    $this->assertTrue($this->controller->isLoggedIn());
    $this->unAuthenticate();
  }

  /**
   * @test
   */
  public function getLoggedInPerson()
  {
    $this->assertSame(null, $this->controller->getLoggedInPerson());

    $this->authenticate('bvisto');
    $this->assertSame(911828, $this->controller->getLoggedInPerson()->getPersonId());
    $this->unAuthenticate();

    Gatekeeper::setUsername('');
    $this->set('\Gustavus\Gatekeeper\Gatekeeper', 'loggedIn', true);
    $this->assertSame((int) '-1', $this->controller->getLoggedInPerson()->getPersonId());
    $this->unAuthenticate();
  }

  /**
   * @test
   */
  public function getLoggedInPersonNoPerson()
  {
    $this->assertSame(null, $this->controller->getLoggedInPerson());

    $this->authenticate('arst');
    $this->assertSame((int) '-1', $this->controller->getLoggedInPerson()->getPersonId());
    $this->unAuthenticate();
  }

  /**
   * @test
   */
  public function getLoggedInPersonId()
  {
    $this->assertSame(null, $this->controller->getLoggedInPersonId());

    $this->authenticate('arst');
    $this->assertSame((int) '-1', $this->controller->getLoggedInPersonId());
    $this->unAuthenticate();
  }

  /**
   * @test
   */
  public function getLoggedInUsername()
  {
    $this->assertSame(null, $this->controller->getLoggedInUsername());

    $this->authenticate('arst');
    $this->assertSame('arst', $this->controller->getLoggedInUsername());
    $this->unAuthenticate();
  }

  /**
   * @test
   */
  public function getMethod()
  {
    // will never be set from cli
    $this->assertSame('', $this->controller->getMethod());
  }
}