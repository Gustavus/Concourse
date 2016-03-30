<?php
/**
 * @package Concourse
 * @author  Billy Visto
 */

namespace Gustavus\Concourse;

require_once 'gatekeeper/gatekeeper.class.php';

use Gustavus\TemplateBuilder\Builder as TemplateBuilder,
  Gustavus\Gatekeeper\Gatekeeper,
  Gustavus\Doctrine\EntityManager,
  Gustavus\Doctrine\DBAL,
  Gustavus\TwigFactory\TwigFactory,
  Campus\Pull\People,
  Gustavus\Concourse\RoutingUtil,
  Gustavus\Utility\PageUtil,
  Gustavus\FormBuilderMk2\FormElement,
  Gustavus\FormBuilderMk2\Persistors\ElementPersistor,
  Gustavus\FormBuilderMk2\Persistors\KeyGenerators\UserKeyGenerator,
  Gustavus\FormBuilderMk2\Populators\LegacyElementPopulator,
  Gustavus\FormBuilderMk2\Messaging\StandardMessagingServerFactory,
  Gustavus\FormBuilderMk2\Util\BotLure,
  Gustavus\FormBuilderMK2\ElementRenderers\ElementRenderer,
  Gustavus\Resources\Resource,
  Gustavus\Extensibility\Filters,
  Gustavus\GACCache\GlobalCache,
  Doctrine\DBAL\Connection,
  InvalidArgumentException,
  UnexpectedValueException;

/**
 * Shared controller for all Concourse applications
 *
 * @package Concourse
 * @author  Billy Visto
 */
abstract class Controller
{
  /**
   * Contains the default Campus API key for all Concourse applications.
   *
   * @var string
   */
  protected $apiKey = '';

  /**
   * @var string
   */
  protected $applicationTitle = 'Application Title';

  /**
   * @var string
   */
  protected $subtitle = '';

  /**
   * @var string
   */
  protected $content = '';

  /**
   * @var string
   */
  protected $focusBox = '';

  /**
   * @var string
   */
  protected $localNavigation = '';

  /**
   * @var string
   */
  protected $stylesheets = '';

  /**
   * @var string
   */
  protected $javascripts = '';

  /**
   * @var array
   */
  protected $breadCrumbs = [];

  /**
   * @var array
   */
  protected $templatePreferences = [];

  /**
   * Entity manager to use if a new one isn't requested
   *
   * @var Doctrine\ORM\EntityManager
   */
  private $em;

  /**
   * If we want a fresh entity manager, it gets stored here in case we need it
   *
   * @var Doctrine\ORM\EntityManager
   */
  private $newEm;

  /**
   * DBAL connection
   *
   * @var Doctrine\DBAL\Connection
   */
  private $dbal;

  /**
   * The Twig Environment
   *
   * @var \Twig_Environment
   */
  private $twig;

  /**
   * The alias of the found route we are using
   *
   * @var string
   */
  private $routeAlias;

  /**
   * A collection of persistors that have been used to prepare or restore forms. Stored to allow
   * controllers to work with the form data before it is stored.
   *
   * @var array
   */
  private $persistors;

  /**
   * Constructs the object.
   *
   * @param string $routeAlias The alias of the current route we are using
   */
  public function __construct($routeAlias = null)
  {
    $this->routeAlias = $routeAlias;
  }

  /**
   * Gets the apiKey being used by the application.
   *
   * @return  string
   */
  protected function getApiKey()
  {
    return $this->apiKey;
  }

  /**
   * Gets the title that is set for the page.
   *
   * @return string the page title.
   */
  protected function getTitle()
  {
    return $this->applicationTitle;
  }

  /**
   * Sets the page title.
   *
   * @param string $title the new page title
   * @return $this
   */
  protected function setTitle($title)
  {
    $this->applicationTitle = $title;
    return $this;
  }

  /**
   * Gets the subtitle of the page.
   *
   * @return string the page subtitle
   */
  protected function getSubtitle()
  {
    return $this->subtitle;
  }

  /**
   * Sets the page subtitle.
   *
   * @param string $subtitle the new page subtitle
   * @return $this
   */
  protected function setSubtitle($subtitle)
  {
    $this->subtitle = $subtitle;
    return $this;
  }

  /**
   * Gets the content of the page.
   *
   * @return string the page content
   */
  protected function getContent()
  {
    return $this->content;
  }

  /**
   * Sets the page content.
   *
   * @param string $content the new page content
   * @return $this
   */
  protected function setContent($content)
  {
    $this->content = $content;
    return $this;
  }

  /**
   * Adds to the page content.
   *
   * @param string $content the new page content
   * @return $this
   */
  protected function addContent($content)
  {
    $this->content .= $content;
    return $this;
  }

  /**
   * Gets the focus box HTML on the page.
   *
   * @return string the HTML in the page focusbox
   */
  protected function getFocusBox()
  {
    return $this->focusBox;
  }

  /**
   * Sets the focus box HTML on the page.
   *
   * @param string $focusBox the new page focusbox content.
   * @return $this
   */
  protected function setFocusBox($focusBox)
  {
    $this->focusBox = $focusBox;
    return $this;
  }

  /**
   * Sets the focus box HTML on the page.
   *
   * @param string $focusBox the new page focusbox content.
   * @return $this
   */
  protected function addFocusBox($focusBox)
  {
    $this->focusBox .= $focusBox;
    return $this;
  }

  /**
   * Gets the stylesheets HTML on the page.
   *
   * @return string the stylesheets HTML on the page
   */
  protected function getStylesheets()
  {
    return $this->stylesheets;
  }

  /**
   * Sets the stylesheets HTML on the page.
   *
   * @param string $stylesheets the new stylesheets HTML on the page
   * @return $this
   */
  protected function setStylesheets($stylesheets)
  {
    $this->stylesheets = $stylesheets;
    return $this;
  }

  /**
   * Adds the stylesheets to the page.
   *
   * @param string $stylesheets the additional stylesheets HTML for the page
   * @return $this
   */
  protected function addStylesheets($stylesheets)
  {
    $this->stylesheets .= $stylesheets;
    return $this;
  }

  /**
   * Gets the javascript content on the page.
   *
   * @return string the javascript content on the page
   */
  protected function getJavascripts()
  {
    return $this->javascripts;
  }

  /**
   * Sets the javascript to be added on the page.
   *
   * @param string $javascripts the new javascripts HTML on the page
   * @return $this
   */
  protected function setJavascripts($javascripts)
  {
    $this->javascripts = $javascripts;
    return $this;
  }

  /**
   * Adds the javascripts to the page.
   *
   * @param string $javascripts the additional javascripts HTML for the page
   * @return $this
   */
  protected function addJavascripts($javascripts)
  {
    $this->javascripts .= $javascripts;
    return $this;
  }

  /**
   * Sets bread crumbs
   *
   * @param array $breadCrumbs Array of arrays with text and url or alias as keys
   * @return $this
   */
  protected function setBreadCrumbs(array $breadCrumbs)
  {
    $this->breadCrumbs = $breadCrumbs;
    return $this;
  }

  /**
   * Gets breadcrumbs. If empty, it will look in the router for bread crumbs
   *
   * @return array
   */
  protected function getBreadCrumbs()
  {
    if (empty($this->breadCrumbs)) {
      $crumbs = $this->findBreadCrumbsFromRoute();
    } else {
      $crumbs = $this->breadCrumbs;
    }
    return $this->urlifyAliasesInCrumbs($crumbs);
  }

  /**
   * Converts aliases in the breadcrumbs array to urls
   *
   * @param  array  $crumbs Array of arrays with keys of text and url or alias.
   * @return array
   */
  private function urlifyAliasesInCrumbs(array $crumbs)
  {
    foreach ($crumbs as &$crumb) {
      if (isset($crumb['alias'])) {
        if (is_array($crumb['alias'])) {
          $alias = key($crumb['alias']);
          $params = current($crumb['alias']);
        } else {
          $alias = $crumb['alias'];
          $params = [];
        }
        $crumb['url'] = $this->buildUrl($alias, $params);
        unset($crumb['alias']);
      }
    }
    return $crumbs;
  }

  /**
   * Gets the bread crumbs from the routing configuration for the current alias
   *
   * @return array
   */
  private function findBreadCrumbsFromRoute()
  {
    if (empty($this->routeAlias)) {
      return [];
    }
    return RoutingUtil::getBreadCrumbs($this->getRoutingConfiguration(), $this->routeAlias);
  }

  /**
   * Returns the local navigation parameters
   *
   * @return array|string Either an array for \Gustavus\LocalNavigation\ItemFactory, or string of html
   */
  abstract protected function getLocalNavigation();

  /**
   * Sets the local navigation
   *
   * @param string|array $localNavigation Either an array for \Gustavus\LocalNavigation/ItemFactory, or string of html
   * @return $this
   */
  protected function setLocalNavigation($localNavigation)
  {
    $this->localNavigation = $localNavigation;
    return $this;
  }

  /**
   * Gets the template preferences for the page.
   *
   * @return array the template preferences
   */
  protected function getTemplatePreferences()
  {
    return $this->templatePreferences;
  }

  /**
   * Sets the template preferences for the page.
   *
   * @param array $templatePreferences the new TemplatePreferences
   * @return $this
   */
  protected function setTemplatePreferences(array $templatePreferences)
  {
    $this->templatePreferences = $templatePreferences;
    return $this;
  }

  /**
   * Adds preferences to the template preferences for the page.
   *
   * @param array $templatePreferences the additional TemplatePreferences
   * @return $this
   */
  protected function addTemplatePreferences(array $templatePreferences)
  {
    $this->templatePreferences = array_merge($this->templatePreferences, $templatePreferences);
    return $this;
  }

  /**
   * Renders the page with the pre-set properties.
   *
   * @return string
   */
  protected function renderPage()
  {
    // look for session messages.
    $this->addSessionMessages();

    $args = [
      'title'               => $this->getTitle(),
      'subtitle'            => $this->getSubtitle(),
      'content'             => $this->getContent(),
      'localNavigation'     => $this->getLocalNavigation(),
      'focusBox'            => $this->getFocusBox(),
      'stylesheets'         => $this->getStylesheets(),
      'javascripts'         => $this->getJavascripts(),
      'breadCrumbAdditions' => $this->getBreadCrumbs(),
    ];

    return (new TemplateBuilder($args, $this->getTemplatePreferences()))->render();
  }

  /**
   * Renders a template specified in $view out in the GAC template
   *
   * @param  string $view       path to the template view
   * @param  array  $parameters parameters to pass to the view
   * @return string
   */
  protected function renderTemplate($view, array $parameters = array())
  {
    $this->addContent($this->renderView($view, $parameters));
    return $this->renderPage();
  }

  /**
   * Renders a twig template specified in $view
   *
   * @param  string|array $view  path to the template view or associative array with key being the namespace, and the path to the template view as the value.
   * @param  array  $parameters parameters to pass to the view
   * @param  boolean  $modifyEnvironment Whether to make sure the view directory is in the environment or not. Passes view on untouched if false.
   * @return string
   */
  protected function renderView($view, array $parameters = array(), $modifyEnvironment = true)
  {
    if (is_array($view)) {
      $namespace = key($view);
      if ($modifyEnvironment) {
        // we want to make sure the view directory is in the current environment
        $viewDir = dirname($view[$namespace]);
        $viewFile = basename($view[$namespace]);
      } else {
        $viewDir = null;
        $viewFile = $view[$namespace];
      }
      $view = sprintf('@%s/%s', $namespace, $viewFile);
    } else {
      $namespace = null;
      if ($modifyEnvironment) {
        // we want to make sure the view directory is in the current environment
        $viewDir = dirname($view);
        $view    = basename($view);
      } else {
        $viewDir = null;
      }
    }
    return $this->getTwigEnvironment($viewDir, $namespace)->render($view, $parameters);
  }

  /**
   * Gets the twig environment set up with renderView
   *
   * @param  string $viewDir  Path to the view directory
   * @param  string $viewNamespace Namespace of the view
   *
   * @throws  UnexpectedValueException If a view directory is not specified and $this->twig doesn't exist.
   * @return \Twig_Environment
   */
  protected function getTwigEnvironment($viewDir = null, $viewNamespace = null)
  {
    if (!empty($viewDir)) {
      $this->setUpTwig($viewDir, $viewNamespace);
    } else if (empty($this->twig)) {
      throw new UnexpectedValueException('Twig does not exist and needs a view directory specified to be built.');
    }

    return $this->twig;
  }

  /**
   * Adds a path to TwigEnvironment's loader
   *
   * @param string $path Path to add
   * @param  string $pathNamespace Namespace of the view path
   * @return  $this
   */
  protected function addTwigLoaderPath($path, $pathNamespace = null)
  {
    $this->setUpTwig($path, $pathNamespace);
    return $this;
  }

  /**
   * Resets the paths for TwigEnvironment's loader
   *
   * @return $this
   */
  protected function resetTwigLoaderPaths()
  {
    if (isset($this->twig)) {
      $this->twig->getLoader()->setPaths([]);
    }
    return $this;
  }

  /**
   * Sets up the Twig environment with $viewDir in the loader paths
   *
   * @param string $viewDir path to the twig templates directory
   * @param  string $viewNamespace Namespace of the view
   * @return  void
   */
  private function setUpTwig($viewDir, $viewNamespace = null)
  {
    if (!isset($this->twig)) {
      Filters::add('twigEnvironmentSetUp', function($twigEnv) {
        // check to see if we need to add the extension
        $extension = new ConcourseTwigExtension($this);
        if (!$twigEnv->hasExtension($extension->getName())) {
          // add concourse extension
          $twigEnv->addExtension($extension);
        }
        return $twigEnv;
      });
      // we don't want TwigFactory to cache things since we are doing our own caching.
      $this->twig = TwigFactory::getTwigFilesystem($viewDir, false);
    }
    // make sure the specified path is in the loader
    if (!empty($viewNamespace)) {
      // we want to make sure this namespace isn't included already.
      if (!in_array($viewDir, $this->twig->getLoader()->getPaths($viewNamespace))) {
        $this->twig->getLoader()->addPath($viewDir, $viewNamespace);
      }
    } else {
      // we are working with Twig's main namespace, and can't just pass null on
      if (!in_array($viewDir, $this->twig->getLoader()->getPaths())) {
        $this->twig->getLoader()->addPath($viewDir);
      }
    }
  }

  /**
   * Renders the template with the specified error message
   *
   * @param  string $errorMessage
   * @return string
   */
  protected function renderErrorPage($errorMessage = '')
  {
    $this->addError($errorMessage);
    return $this->renderPage();
  }

  /**
   * Adds error text to content
   *
   * @param string $errorMessage
   * @return  $this
   */
  protected function addError($errorMessage = '')
  {
    $this->content .= '<div class="error">'. $errorMessage . '</div>';
    return $this;
  }

  /**
   * Adds error text to top of content
   *
   * @param string $errorMessage
   * @return  $this
   */
  protected function addErrorToTop($errorMessage = '')
  {
    $this->content = '<div class="error">'. $errorMessage . '</div>' . $this->content;
    return $this;
  }

  /**
   * Adds message text to content
   *
   * @param string $message
   * @return  $this
   */
  protected function addMessage($message = '')
  {
    $this->content .= '<div class="message">'. $message . '</div>';
    return $this;
  }

  /**
   * Adds message text to the top of the content
   *
   * @param string $message
   * @return  $this
   */
  protected function addMessageToTop($message = '')
  {
    $this->content = '<div class="message">'. $message . '</div>' . $this->content;
    return $this;
  }

  /**
   * Forces a user to login
   *
   * @param  string $returnToURL  URL to send users to after successfully logging in
   * @return void
   */
  protected function login($returnToURL = '')
  {
    if (empty($returnToURL)) {
      $returnToURL = 'https://' . $_SERVER['SERVER_NAME'] . $_SERVER['REQUEST_URI'];
    }
    Gatekeeper::logIn($returnToURL);
  }

  /**
   * Checks to see if a user is logged in.
   * @return boolean true if the user is logged in, false otherwise
   */
  protected function isLoggedIn()
  {
    return Gatekeeper::isLoggedIn();
  }

  /**
   * Gets the username of the logged in user.
   * @return string|null returns a string if the user is logged in, null otherwise
   */
  protected function getLoggedInUsername()
  {
    return Gatekeeper::getUsername();
  }

  /**
   * Gets the user id of the logged in user.
   * @return int|null returns the id number of the logged in user or null if they are not logged in.
   */
  protected function getLoggedInPersonId()
  {
    $person = $this->getLoggedInPerson();
    return ($person !== null) ? $person->getPersonId() : null;
  }

  /**
   * Gets the Campus\Person of the logged in user.
   * @return Campus\Person|null returns the logged in Person if found. null otherwise
   */
  protected function getLoggedInPerson()
  {
    if ($this->isLoggedIn()) {
      $person = Gatekeeper::getUser();
      if (is_object($person)) {
        if ($person->getPersonId() !== -1) {
          // we don't want to use Gatekeepers apiKey, so lets get the personId from gatekeeper's user and make our own with our application specific apiKey
          $campusPuller = new People($this->apiKey);
          $person = $campusPuller->setPersonId($person->getPersonId())->current();
        }
        return $person;
      }
    }
    return null;
  }

  /**
   * Sets up and returns the entity manager
   *
   * @param  string $applicationPath full path to the application
   * @param  string $dbName          name of the database in the config file
   * @param  boolean $new            true if we want a new instance.
   * @param  \PDO    $pdo            Optional pre-existing connection to use
   * @param  string  $charset        Charset to default the connection to
   * @return Doctrine\ORM\EntityManager
   */
  protected function getEM($applicationPath, $dbName = '', $new = false, $pdo = null, $charset = null)
  {
    if ($new) {
      $this->newEm = EntityManager::getEntityManager($applicationPath, $pdo, $dbName, $charset);
      return $this->newEm;
    }
    if (!isset($this->em)) {
      $this->em = EntityManager::getEntityManager($applicationPath, $pdo, $dbName, $charset);
    }
    return $this->em;
  }

  /**
   * Gets the new entity manager generated in getEm with $new = true. Or generates a new one itself if a new one hasn't been set yet.
   *
   * @param  string $applicationPath full path to the application
   * @param  string $dbName          name of the database in the config file
   * @param  \PDO    $pdo            Optional pre-existing connection to use
   * @param  string  $charset        Charset to default the connection to
   * @return Doctrine\ORM\EntityManager
   */
  protected function getNewEM($applicationPath = '', $dbName = '', $pdo = null, $charset = null)
  {
    if (!isset($this->newEm)) {
      $this->newEm = EntityManager::getEntityManager($applicationPath, $pdo, $dbName, $charset);
    }
    return $this->newEm;
  }

  /**
   * Sets up and returns a DBAL instance
   *
   * @param  string $projectName Name of the project to get the db connection for
   * @param  \PDO   $pdo         Optional pre-existing connection to use
   * @param  string $charset     Charset to default the connection to
   * @return Connection
   */
  protected function getDBAL($projectName, $pdo = null, $charset = null)
  {
    if (!isset($this->dbal)) {
      $this->dbal = DBAL::getDBAL($projectName, $pdo, $charset);
    }
    return $this->dbal;
  }

  /**
   * Sets the DBAL connection for us to use
   *
   * @param Connection $dbal Connection to use
   * @return  $this
   */
  protected function setDBAL(Connection $dbal)
  {
    $this->dbal = $dbal;
    return $this;
  }

  /**
   * Gets the current request method of the server
   *
   * @return string
   */
  protected function getMethod()
  {
    return isset($_SERVER['REQUEST_METHOD']) ? $_SERVER['REQUEST_METHOD'] : '';
  }

  /**
   * Redirects to the specified path
   *
   * @param  string $path path to redirect to.
   * @param  integer $statusCode Redirection status code
   * @return void
   */
  protected function redirect($path = '/', $statusCode = 303)
  {
    PageUtil::redirect($path, $statusCode);
  }

  /**
   * Redirects user to new path with the specified message to be displayed if it goes through the router
   *
   * @param  string $path    path to redirect to.
   * @param  string $message message to display on redirect
   * @param  integer $statusCode Redirection status code
   * @return void
   */
  protected function redirectWithMessage($path = '/', $message = '', $statusCode = 303)
  {
    $this->setSessionMessage($message, false, $path);
    $this->redirect($path, $statusCode);
  }

  /**
   * Redirects user to new path with the specified error message to be displayed if it goes through the router
   *
   * @param  string $path    path to redirect to.
   * @param  string $message message to display on redirect
   * @param  integer $statusCode Redirection status code
   * @return void
   */
  protected function redirectWithError($path = '/', $message = '', $statusCode = 303)
  {
    $this->setSessionMessage($message, true, $path);
    $this->redirect($path, $statusCode);
  }

  /**
   * Sets the message to be displayed on the next time the page is loaded
   *
   * @param string  $message message to display
   * @param boolean $isError whether this is an error message or not
   * @param string  $path of the page the message should be displayed on
   * @return  $this
   */
  protected function setSessionMessage($message = '', $isError = false, $path = null)
  {
    if ($path === null) {
      $path = isset($_SERVER['REQUEST_URI']) ? $_SERVER['REQUEST_URI'] : null;
    }
    PageUtil::setSessionMessage($message, $isError, $path);

    return $this;
  }

  /**
   * Adds to the message to be displayed on the next time the page is loaded
   *
   * @param string  $message message to display
   * @param boolean $isError whether this is an error message or not
   * @param string  $path of the page the message should be displayed on
   * @return  $this
   */
  protected function addSessionMessage($message = '', $isError = false, $path = null)
  {
    if ($path === null) {
      $path = isset($_SERVER['REQUEST_URI']) ? $_SERVER['REQUEST_URI'] : null;
    }
    if ($isError) {
      $currentMessage = PageUtil::getSessionErrorMessage($path);
    } else {
      $currentMessage = PageUtil::getSessionMessage($path);
    }
    if (!empty($currentMessage)) {
      $message = sprintf('%s<br/><br/>%s', $currentMessage, $message);
    }

    PageUtil::setSessionMessage($message, $isError, $path);

    return $this;
  }

  /**
   * Adds session messages to the page if any are set
   *
   * @return $this
   */
  protected function addSessionMessages()
  {
    $path = isset($_SERVER['REQUEST_URI']) ? $_SERVER['REQUEST_URI'] : null;

    // look for session messages that we may need to add to the top of the content
    $message = PageUtil::getSessionMessage($path);
    if (!empty($message)) {
      $this->addMessageToTop($message);
    }

    $errorMessage = PageUtil::getSessionErrorMessage($path);
    if (!empty($errorMessage)) {
      $this->addErrorToTop($errorMessage);
    }

    return $this;
  }

  /**
   * Adds a meta tag telling robots not to index this page.
   *
   * @return  void
   */
  protected function addNoRobotsTag()
  {
    Filters::add('head', function($content) {
      return $content . '<meta name="robots" content="noindex" />';
    });
  }

  /**
   * Checks to see if the logged in user has permissions in gatekeeper
   *
   * @param  string $applicationName Application to check permissions in
   * @param  array|string $permissions  permissions to check
   * @param  string $logInLevel      Gatekeeper's login level
   * @return boolean Whether the user has permissions or not
   */
  protected function checkPermissions($applicationName, $permissions, $logInLevel = null)
  {
    assert('is_array($permissions) || is_string($permissions)');

    if ($logInLevel === null) {
      $logInLevel = Gatekeeper::LOG_IN_LEVEL_ALL;
    }
    if (is_string($permissions)) {
      $permissions = [$permissions];
    }
    return Gatekeeper::checkPermissions($applicationName, $logInLevel, $permissions);
  }

  /**
   * Returns the routing configuration
   *
   * @return array|string Either the full routing array or the path to the routing configuration file
   */
  abstract protected function getRoutingConfiguration();

  /**
   * Calls RoutingUtil::buildUrl with the routeConfig from getRoutingConfiguration
   *
   * @param  string $alias       Alias to build url for
   * @param  array  $parameters  Params to put into url
   * @param  string $baseDir     Applications web root
   * @param  boolean $fullUrl   Whether you want the full url or just the relative url
   * @return string
   */
  public function buildUrl($alias, array $parameters = array(), $baseDir = '', $fullUrl = false)
  {
    return RoutingUtil::buildUrl($this->getRoutingConfiguration(), $alias, $parameters, $baseDir, $fullUrl);
  }

  /**
   * Calls RoutingUtil::forward with the routeConfig from getRoutingConfiguration
   *
   * @param  string $alias       Alias to forward to
   * @param  array  $parameters  Parameters to send to the handler
   * @return mixed
   */
  protected function forward($alias, array $parameters = array())
  {
    return RoutingUtil::forward($this->getRoutingConfiguration(), $alias, $parameters);
  }

  /**
   * Checks to see if we have a form to restore to return. If not, we prepare
   * Builds a form using FormBuilder and adds BotLure to the form.
   *
   * @param  string   $formKey               Key the form uses for saving and submiting
   * @param  callable|array $configuration   Callback used to get the configuration array if needed, or the configuration array itself.
   *     <strong>Note:</strong> Passing a callable is recommended
   * @param  array    $callableParameters    Parameters to pass onto the callable
   * @param  mixed $ttl Amount of time the form is kept around. A value of null, 0, or <0 will never expire. Default: 30 days (2592000)
   *
   * @throws  InvalidArgumentException If $configuration is not an array or a callable
   * @return FormBuilder
   */
  protected function buildForm($formKey, $configuration, $callableParameters = null, $ttl = 2592000)
  {
    $form = $this->restoreForm($formKey);
    if ($form === null) {
      // no form to restore. Need to build one.
      if (is_callable($configuration)) {
        if (empty($callableParameters)) {
          $config = call_user_func($configuration);
        } else {
          $config = call_user_func_array($configuration, $callableParameters);
        }
      } else {
        if (!is_array($configuration)) {
          // not an array. invalid argument.
          throw new InvalidArgumentException('Configuration must be either an array or a callable.');
        }
        $config = $configuration;
      }
      $form = $this->prepareForm($config, $formKey, $ttl);
      // restore form in case we have post-data to populate with
      $form = $this->restoreForm($formKey);
    }
    return $form;
  }

  /**
   * Adds CSS and JS for FormBuilder
   *
   * @param  ElementRenderer $renderer Renderer to get the resources for
   * @param  array $extraCSSResources Extra css resources to include
   * @param  array $extraJSResources Whether to include the student orgs js as well
   * @return  $this
   */
  protected function addFormResources(ElementRenderer $renderer, array $extraCSSResources = null, array $extraJSResources = null)
  {
    $resources = $renderer->getExternalResources();
    $styles = (isset($resources['css'])) ? $resources['css'] : [];
    $js     = (isset($resources['js'])) ? $resources['js'] : [];
    if (!empty($extraCSSResources)) {
      $styles = array_merge($styles, $extraCSSResources);
    }

    if (!empty($styles)) {
      $this->addStylesheets(sprintf(
          '<link rel="stylesheet" type="text/css" href="%s"/>',
          Resource::renderCSS($styles)
      ));
    }

    if (!empty($extraJSResources)) {
      $js = array_merge($js, $extraJSResources);
    }

    if (!empty($js)) {
      $this->addJavascripts(sprintf(
          '<script type="text/javascript">
            require.config({
              shim: {
                "%1$s": ["baseJS"]
              }
            });
            require(["%1$s"]);
          </script>',
          Resource::renderResource($js)
      ));
    }

    return $this;
  }

  /**
   * Retrieves a persistor to retrieve persistent data for the specified key. The key will be
   * associated either with the currently active user or the current session.
   *
   * @param string $key
   *  The key to use for retrieving persistent data.
   *
   * @return ElementPersistor
   *  An ElementPersistor using the specified key for retrieving and storing data.
   */
  protected function getElementPersistor($key)
  {
    if (empty($key)) {
      // We can use an integer key to represent the "global" key.
      $key = 0;
    }

    if (!isset($this->persistors[$key])) {
      if (!isset($this->persistors)) {
        $this->persistors = [];
      }

      $this->persistors[$key] = new ElementPersistor(new UserKeyGenerator($key));
    }

    return $this->persistors[$key];
  }

  /**
   * Retrieves the factory to use for building new MessagingServer instances for ElementPersistors
   * which do not already have one.
   *
   * @return MessagingServerFactory
   *  The MessagingServerFactory to use for building new MessagingServer instances.
   */
  protected function getMessagingServerFactory()
  {
    return new StandardMessagingServerFactory();
  }

  /**
   * Retrieves the populator to be used for populating restored forms.
   *
   * @return ElementPopulator
   *  The populator to be used for populating forms retrieved by the restoreForm method.
   */
  protected function getElementPopulator()
  {
    return new LegacyElementPopulator();
  }

  /**
   * Prepares a form using FormBuilder and adds BotLure to it
   *
   * @param  array   $config   Configuration array to build a form from
   * @param  string  $formKey  Key of the form
   * @param  mixed   $ttl      Amount of time the form is kept around. A value of null, 0, or <0 will never expire. Default: 30 days (2592000)
   *
   * @throws InvalidArgumentException
   *  if $config is not an array nor a FormElement instance.
   *
   * @return  FormElement The prepared form
   */
  protected function prepareForm($config, $formKey = null, $ttl = 2592000)
  {
    $persistor = $this->getElementPersistor($formKey);

    // Use the key to seed the prng. This will allow us to generate the same random names during
    // a given session.
    $seed = crc32($persistor->getKey());
    mt_srand($seed);

    $form = is_array($config) ? FormElement::buildElement($config) : $config;

    if ($form instanceof FormElement) {
      // Add the BotLure to attempt to keep bots from submitting the form.
      $form->addChildren(new BotLure());

      // Add the form's key (legacy stuff).
      $form->setAttribute('fbkey', $persistor->getKey());

      // Configure persistor.
      $persistor->setElement($form);

      if (!$persistor->hasPersistentData()) {
        $persistor->setMessagingServer($this->getMessagingServerFactory()->buildMessagingServer());
      }

      if (isset($ttl)) {
        $persistor->setTTL($ttl);
      }
    } else {
      // Uh oh...?
      throw new InvalidArgumentException('$config must be an array or a FormElement instance.');
    }

    return $form;
  }

  /**
   * Restores a form from FormBuilder
   *
   * @param  string  $formKey     Key of the form to restore
   * @return FormElement
   */
  protected function restoreForm($formKey = null)
  {
    $persistor = $this->getElementPersistor($formKey);

    return !$persistor->hasExpired() ? $persistor->getElement($this->getElementPopulator()) : null;
  }

  /**
   * Flushes a form for FormBuilder
   *
   * @param  string $formKey Key of the form to flush
   * @return FormElement
   */
  protected function flushForm($formKey = null)
  {
    $persistor = $this->getElementPersistor($formKey);

    $element = !$persistor->hasExpired() ? $persistor->getElement() : null;

    $persistor->clear();

    return $element;
  }

  /**
   * Gets the cache data store for applications to interact with
   *
   * @return \Gustavus\GACCache\CacheDataStore
   */
  protected function getCache()
  {
    return GlobalCache::getGlobalDataStore();
  }
}
