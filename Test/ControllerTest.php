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
  Gustavus\Concourse\Controller,
  Gustavus\Concourse\Test\ControllerTestController,
  Gustavus\Gatekeeper\Gatekeeper,
  Gustavus\FormBuilderMk2\ElementRenderers\TwigElementRenderer,
  Gustavus\FormBuilderMk2\FormBuilder;

/**
 * @package Concourse
 * @subpackage Test
 * @author  Billy Visto
 */
class ControllerTest extends Test
{
  /**
   * Base Directory for urls
   */
  const BASE_DIR = '/Gustavus/Concourse/ControllerTest/';

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
    $this->controller = new TestObject(new ControllerTestController('indexTwo'));

    foreach ($this->controllerProperties as $key => $value) {
      $function = 'set' . ucfirst($key);
      if (is_callable(array($this->controller, $function))) {
        $this->controller->$function($value);
      }
    }
    $_SERVER['SCRIPT_NAME'] = self::BASE_DIR . 'index.php';
    $this->unAuthenticate();
  }

  /**
   * destroys the object after every test
   */
  public function tearDown()
  {
    unset($this->controller);
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
  public function addStylesheets()
  {
    $this->controller->addStylesheets('hello');
    $this->assertSame($this->controllerProperties['stylesheets'] . 'hello', $this->controller->getStylesheets());
  }

  /**
   * @test
   */
  public function addJavascripts()
  {
    $this->controller->addJavascripts('hello');
    $this->assertSame($this->controllerProperties['javascripts'] . 'hello', $this->controller->getJavascripts());
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
  public function getEM()
  {
    $em = $this->controller->getEM('/cis/lib/Gustavus/Menu', 'menu');
    $this->assertInstanceOf('Doctrine\ORM\EntityManager', $em);

    $newEm = $this->controller->getEM('/cis/lib/Gustavus/Menu', 'menu', true);
    $this->assertInstanceOf('Doctrine\ORM\EntityManager', $newEm);

    $this->assertNotSame($em, $newEm);
  }

  /**
   * @test
   */
  public function getNewEM()
  {
    $em = $this->controller->getEM('/cis/lib/Gustavus/Menu', 'menu');
    $newEm = $this->controller->getNewEM('/cis/lib/Gustavus/Menu', 'menu');
    $this->assertNotSame($em, $newEm);

    $newNewEm = $this->controller->getEM('/cis/lib/Gustavus/Menu', 'menu', true);
    $newestEm = $this->controller->getNewEM();
    $this->assertSame($newNewEm, $newestEm);
  }

  /**
   * @test
   */
  public function getDBAL()
  {
    $dbal = $this->controller->getDBAL('menu');
    $this->assertInstanceOf('Doctrine\DBAL\Connection', $dbal);
  }

  /**
   * @test
   */
  public function getMethod()
  {
    // will never be set from cli
    $this->assertSame('', $this->controller->getMethod());
  }

  /**
   * @test
   */
  public function addError()
  {
    $this->controller->setContent('arst');
    $this->controller->addError('Help!');
    $this->assertSame('arst<p class="error">Help!</p>', $this->controller->getContent());
  }

  /**
   * @test
   */
  public function addErrorToTop()
  {
    $this->controller->setContent('arst');
    $this->controller->addErrorToTop('Help!');
    $this->assertSame('<p class="error">Help!</p>arst', $this->controller->getContent());
  }

  /**
   * @test
   */
  public function addMessage()
  {
    $this->controller->setContent('');
    $this->controller->addMessage('Help!');
    $this->assertSame('<p class="message">Help!</p>', $this->controller->getContent());
  }

  /**
   * @test
   */
  public function addMessageToTop()
  {
    $this->controller->addMessageToTop('Help!');
    $this->assertSame('<p class="message">Help!</p>' . $this->controllerProperties['content'], $this->controller->getContent());
  }

  /**
   * @test
   */
  public function addContent()
  {
    $this->controller->addContent('Help!');
    $this->assertSame($this->controllerProperties['content'] . 'Help!', $this->controller->getContent());
  }

  /**
   * @test
   */
  public function checkPermissions()
  {
    $this->assertFalse($this->controller->checkPermissions('menu', 'admin'));

    $this->authenticate('bvisto');
    $this->assertTrue($this->controller->checkPermissions('menu', ['manager']));
    $this->unAuthenticate();
  }

  /**
   * @test
   */
  public function renderPage()
  {
    $this->controller->setContent('helloarst');
    $actual = $this->controller->renderPage();
    $this->assertTrue(strpos($actual['content'], 'helloarst') !== false);
  }

  /**
   * @test
   */
  public function setSessionMessage()
  {
    $this->controller->setSessionMessage('testMessage');
    $this->controller->setSessionMessage('testErrorMessage', true);

    $actual = $this->controller->renderPage();
    $messagePos = strpos($actual['content'], '<p class="message">testMessage');
    $errorMessagePos = strpos($actual['content'], '<p class="error">testErrorMessage');

    $this->assertTrue($errorMessagePos !== false);
    $this->assertTrue($messagePos !== false);
    $this->assertTrue($errorMessagePos < $messagePos);
  }

  /**
   * @test
   */
  public function setAndGetMessage()
  {
    $this->controller->setSessionMessage('testMessage', false, '/test/arst/test');

    $_SERVER['REQUEST_URI'] = '/test/arst/test';

    $this->controller->addSessionMessages();
    $this->assertContains('testMessage', $this->controller->getContent());
  }

  /**
   * @test
   */
  public function redirectWithMessage()
  {
    $this->controller->redirectWithMessage('/', 'someTestMessage');

    $_SERVER['REQUEST_URI'] = '/';
    $actual = $this->controller->renderPage();
    $this->assertTrue(strpos($actual['content'], '<p class="message">someTestMessage') !== false);
  }

  /**
   * @test
   */
  public function redirectWithError()
  {
    $this->controller->redirectWithError('/', 'someTestErrorMessage');

    $_SERVER['REQUEST_URI'] = '/';
    $actual = $this->controller->renderPage();
    $this->assertTrue(strpos($actual['content'], '<p class="error">someTestErrorMessage') !== false);
  }

  /**
   * @test
   */
  public function renderTemplate()
  {
    $actual = $this->controller->renderTemplate('/cis/lib/Gustavus/Concourse/Test/testView.html.twig', ['testParam' => 'TestingTemplate']);
    $this->assertTrue(strpos($actual['content'], 'TestingTemplate') !== false);
  }

  /**
   * @test
   */
  public function renderView()
  {
    $actual = $this->controller->renderView('/cis/lib/Gustavus/Concourse/Test/testView.html.twig', ['testParam' => 'TestingTemplate']);
    $this->assertTrue(strpos($actual, 'TestingTemplate') !== false);
  }

  /**
   * @test
   */
  public function renderViewBuildUrlTemplate()
  {
    $_SERVER['HTTP_HOST'] = 'gustavus.edu';
    $actual = $this->controller->renderView('/cis/lib/Gustavus/Concourse/Test/buildUrlBaseDir.html.twig', []);
    $this->assertSame('https://gustavus.edu/', $actual);
  }

  /**
   * @test
   */
  public function renderNamespaceView()
  {
    $view = ['admin' => '/cis/lib/Gustavus/Concourse/Test/testView.html.twig'];
    $actual = $this->controller->renderView($view, ['testParam' => 'TestingTemplate']);
    $this->assertContains('TestingTemplate', $actual);
  }

  /**
   * @test
   */
  public function setUpTwig()
  {
    $this->controller->setUpTwig('/cis/lib/Gustavus/Concourse/Test');
    $this->assertInstanceOf('Twig_Environment', $this->controller->twig);
  }

  /**
   * @test
   */
  public function setUpTwigWithNamespace()
  {
    $this->controller->setUpTwig('/cis/lib/Gustavus/Concourse/Test', 'admin');

    $paths = $this->controller->twig->getLoader()->getPaths();
    $namePaths = $this->controller->twig->getLoader()->getPaths('admin');
    $this->assertSame($paths, $namePaths);
    $this->assertInstanceOf('Twig_Environment', $this->controller->twig);
  }

  /**
   * @test
   */
  public function addTwigLoaderPathIfNeeded()
  {
    $this->controller->twig = null;
    $this->controller->setUpTwig('/cis/lib/Gustavus/Concourse/Test');
    $this->controller->setUpTwig('/cis/lib/Gustavus');
    $this->assertInstanceOf('Twig_Environment', $this->controller->twig);

    $expected = [
      '/cis/lib/Gustavus/Concourse/Test',
      '/cis/lib/Gustavus',
    ];
    $this->assertSame($expected, $this->controller->twig->getLoader()->getPaths());
  }

  /**
   * @test
   */
  public function getTwigEnvironment()
  {
    $env = $this->controller->getTwigEnvironment('/cis/lib/Gustavus/Concourse/Test');
    $this->assertInstanceOf('Twig_Environment', $env);
  }

  /**
   * @test
   */
  public function getTwigEnvironmentWithNamespace()
  {
    $env = $this->controller->getTwigEnvironment('/cis/lib/Gustavus/Concourse/Test', 'admin');

    $paths = $env->getLoader()->getPaths();
    $namePaths = $env->getLoader()->getPaths('admin');
    $this->assertSame($paths, $namePaths);

    $this->assertInstanceOf('Twig_Environment', $env);
  }

  /**
   * @test
   */
  public function addTwigLoaderPath()
  {
    $this->controller->twig = null;
    $this->controller->addTwigLoaderPath('/cis/lib/Gustavus/Concourse/Test');
    $this->controller->addTwigLoaderPath('/cis/lib/Gustavus');
    $this->controller->addTwigLoaderPath('/cis/lib/Gustavus');
    $this->controller->addTwigLoaderPath('/cis/lib/Gustavus');
    $this->controller->addTwigLoaderPath('/cis/lib/Gustavus/Concourse');

    $twig = $this->controller->getTwigEnvironment('/cis/lib/Gustavus/Concourse/Test');

    $expected = [
      '/cis/lib/Gustavus/Concourse/Test',
      '/cis/lib/Gustavus',
      '/cis/lib/Gustavus/Concourse',
    ];

    $this->assertSame($expected, $twig->getLoader()->getPaths());
  }

  /**
   * @test
   */
  public function resetTwigLoaderPaths()
  {
    $this->controller->twig = null;
    $this->controller->addTwigLoaderPath('/cis/lib/Gustavus/Concourse/Test');
    $this->controller->addTwigLoaderPath('/cis/lib/Gustavus');
    $this->controller->addTwigLoaderPath('/cis/lib/Gustavus/Concourse');

    $twig = $this->controller->getTwigEnvironment('/cis/lib/Gustavus/Concourse/Test');

    $expected = [
      '/cis/lib/Gustavus/Concourse/Test',
      '/cis/lib/Gustavus',
      '/cis/lib/Gustavus/Concourse',
    ];

    $this->assertSame($expected, $twig->getLoader()->getPaths());
    $this->controller->resetTwigLoaderPaths();
    $this->assertEmpty($twig->getLoader()->getPaths());
  }

  /**
   * @test
   */
  public function renderErrorPage()
  {
    $actual = $this->controller->renderErrorPage('This is an error');

    $this->assertTrue(strpos($actual['content'], '<p class="error">This is an error') !== false);
  }

  /**
   * @test
   */
  public function buildUrl()
  {
    $expected = self::BASE_DIR;
    $this->assertSame($expected, $this->controller->buildUrl('index'));
  }

  /**
   * @test
   */
  public function buildUrlParam()
  {
    $expected = '/arst/indexTwo/2';
    $this->assertSame($expected, $this->controller->buildUrl('indexTwo', ['id' => 2], '/arst'));
  }

  /**
   * @test
   */
  public function buildUrlParams()
  {
    $expected = '/arst/indexTwo/2/hello';
    $this->assertSame($expected, $this->controller->buildUrl('indexTwoKey', ['id' => 2, 'key' => 'hello'], '/arst/'));
  }

  /**
   * @test
   * @expectedException OutOfBoundsException
   */
  public function buildUrlParamNotFound()
  {
    $this->assertNull($this->controller->buildUrl('indexT', ['id' => 2]));
  }

  /**
   * @test
   */
  public function buildUrlParamsTwig()
  {
    $expected = '/indexTwo/2/hello';

    $actual = $this->controller->renderTemplate('/cis/lib/Gustavus/Concourse/Test/twigExtension.html.twig');
    $this->assertTrue(strpos($actual['content'], $expected) !== false);
  }

  /**
   * @test
   */
  public function buildUrlWithBaseDir()
  {
    $_SERVER['HTTP_HOST'] = 'gustavus.edu';
    $expected = 'https://gustavus.edu/index/';
    $this->assertSame($expected, $this->controller->buildUrl('index', [], '/index', true));
  }

  /**
   * @test
   */
  public function forward()
  {
    $actual = $this->controller->forward('index');
    $this->assertSame('RouterTestController index()', $actual);
  }

  /**
   * @test
   */
  public function forwardAdvanced()
  {
    $actual = $this->controller->forward('indexTwo', ['id' => 23]);
    $this->assertSame('RouterTestController indexTwo(23)', $actual);
  }

  /**
   * @test
   */
  public function forwardAdvancedTwoParams()
  {
    $actual = $this->controller->forward('indexTwoKey', ['id' => 23, 'key' => 'arst']);
    $this->assertSame('RouterTestController indexThree(23, arst)', $actual);
  }

  /**
   * @test
   */
  public function setAndGetBreadCrumbs()
  {
    $crumbs = [['url' => 'Some Url', 'text' => 'text'], ['url' => 'NExt url', 'text' => 'more text']];
    $this->controller->setBreadCrumbs($crumbs);

    $actual = $this->controller->renderPage();
    $this->assertSame($crumbs, $actual['breadCrumbAdditions']);
  }

  /**
   * @test
   */
  public function getBreadCrumbsFromRouting()
  {
    $expected = [['url' => 'Some Url', 'text' => 'text']];

    $actual = $this->controller->findBreadCrumbsFromRoute();
    $this->assertSame($expected, $actual);
  }

  /**
   * @test
   */
  public function getBreadCrumbsFromRoutingRender()
  {
    $expected = [['url' => 'Some Url', 'text' => 'text']];

    $actual = $this->controller->renderPage();
    $this->assertSame($expected, $actual['breadCrumbAdditions']);
  }

  /**
   * @test
   */
  public function urlifyAliasesInCrumbs()
  {
    $expected = [['text' => 'text', 'url' => self::BASE_DIR], ['url' => 'NExt url', 'text' => 'more text']];
    $crumbs = [['alias' => 'index', 'text' => 'text'], ['url' => 'NExt url', 'text' => 'more text']];
    $actual = $this->controller->urlifyAliasesInCrumbs($crumbs);

    $this->assertSame($expected, $actual);
  }

  /**
   * @test
   * @expectedException InvalidArgumentException
   */
  public function buildFormException()
  {
    $form = $this->controller->buildForm('testForm', 'arst');
    $renderer = new TwigElementRenderer();
    $rendered = $renderer->render($form);
    $this->controller->flushForm('testForm');
  }

  /**
   * @test
   */
  public function buildForm()
  {
    $this->controller->flushForm('testForm');
    $form = $this->controller->buildForm('testForm', $this->getFormConfiguration());
    $renderer = new TwigElementRenderer();
    $rendered = $renderer->render($form);
    $this->assertContains('Some Random Title', $rendered);
    $this->assertContains('nodisplay', $rendered);
  }

  /**
   * @test
   */
  public function buildFormCallable()
  {
    $form = $this->controller->buildForm('testForm', [$this, 'getFormConfiguration']);
    $renderer = new TwigElementRenderer();
    $rendered = $renderer->render($form);
    $this->assertContains('Some Random Title', $rendered);
    $this->assertContains('nodisplay', $rendered);
    $this->controller->flushForm('testForm');
  }

  /**
   * Builds the configuration for a form
   * @return array
   */
  public function getFormConfiguration()
  {
    $config = [
      'name'     => 'sometestform',
      'type'     => 'form',
      'action'   => '/cis/www/someRandomRequestURI',
      'method'   => 'post',
      'children' => [
        [
          'type'  => 'text',
          'title' => 'Some Random Title',
        ],
      ]
    ];
    return $config;
  }

  /**
   * Builds the configuration for a form
   * @return array
   */
  public function getFormConfigurationFake()
  {
    $config = [
      'name'     => 'sometestform',
      'type'     => 'form',
      'action'   => '/cis/www/someRandomRequestURI',
      'method'   => 'post',
      'children' => [
        [
          'type'  => 'text',
          'title' => 'A fake form title',
        ],
      ]
    ];
    return $config;
  }

  /**
   * @test
   * @dependsOn buildForm
   */
  public function buildFormAfterAlreadyBuilt()
  {
    $this->buildForm();
    $form = $this->controller->buildForm('testForm', [$this, 'getFormConfigurationFake']);
    $renderer = new TwigElementRenderer();
    $rendered = $renderer->render($form);
    $this->assertNotContains('A fake form title', $rendered);
    $this->assertContains('Some Random Title', $rendered);
    $this->assertContains('nodisplay', $rendered);
    $this->controller->flushForm('testForm');
  }

  /**
   * @test
   */
  public function buildFormCallbaleParam()
  {
    $form = $this->controller->buildForm('testForm2', [$this, 'getFormConfigurationWithParam'], ['Some Random Text']);
    $renderer = new TwigElementRenderer();
    $rendered = $renderer->render($form);
    $this->assertContains('Some Random Text', $rendered);
    $this->assertContains('nodisplay', $rendered);
    $this->controller->flushForm('testForm');
  }

  /**
   * Builds the configuration for a form
   * @param  string $title title to use
   * @return array
   */
  public function getFormConfigurationWithParam($title)
  {
    $config = [
      'name'     => 'sometestform',
      'type'     => 'form',
      'action'   => '/cis/www/someRandomRequestURI',
      'method'   => 'post',
      'children' => [
        [
          'type'  => 'text',
          'title' => $title,
        ],
      ]
    ];
    return $config;
  }
}