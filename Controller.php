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
  Gustavus\TwigFactory\TwigFactory,
  Campus\Pull\People,
  Gustavus\Concourse\RoutingUtil,
  Gustavus\Utility\PageUtil,
  Gustavus\FormBuilderMk2\FormBuilder,
  Gustavus\FormBuilderMk2\Util\BotLure,
  InvalidArgumentException;

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
   * The Twig Environment
   *
   * @var \Twig_Environment
   */
  private $twig;

  /**
   * The alias of the found route we are using
   */
  private $routeAlias;

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
        $crumb['url'] = $this->buildUrl($crumb['alias']);
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
   * @param  string $view       path to the template view
   * @param  array  $parameters parameters to pass to the view
   * @return string
   */
  protected function renderView($view, array $parameters = array())
  {
    return $this->getTwigEnvironment(dirname($view))->render(basename($view), $parameters);
  }

  /**
   * Gets the twig environment set up with renderView
   *
   * @param  string $viewDir  Path to the view directory
   * @return \Twig_Environment
   */
  protected function getTwigEnvironment($viewDir)
  {
    $this->setUpTwig($viewDir);

    return $this->twig;
  }

  /**
   * Adds a path to TwigEnvironment's loader
   *
   * @param string $path Path to add
   * @return  void
   */
  protected function addTwigLoaderPath($path)
  {
    $this->setUpTwig($path);
  }

  /**
   * Resets the paths for TwigEnvironment's loader
   *
   * @return void
   */
  protected function resetTwigLoaderPaths()
  {
    if (isset($this->twig)) {
      $this->twig->getLoader()->setPaths([]);
    }
  }

  /**
   * Sets up the Twig environment with $viewDir in the loader paths
   *
   * @param string $viewDir path to the twig templates directory
   * @return  void
   */
  private function setUpTwig($viewDir)
  {
    if (!isset($this->twig)) {
      $this->twig = TwigFactory::getTwigFilesystem($viewDir);
      // check to see if we need to add the extension
      $extension = new ConcourseTwigExtension($this);
      if (!$this->twig->hasExtension($extension->getName())) {
        // add concourse extension
        $this->twig->addExtension($extension);
      }
    }
    // make sure the specified path is in the loader
    if (!in_array($viewDir, $this->twig->getLoader()->getPaths())) {
      $this->twig->getLoader()->addPath($viewDir);
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
   * @return  void
   */
  protected function addError($errorMessage = '')
  {
    $this->content .= '<p class="error">'. $errorMessage . '</p>';
  }

  /**
   * Adds error text to top of content
   *
   * @param string $errorMessage
   * @return  void
   */
  protected function addErrorToTop($errorMessage = '')
  {
    $this->content = '<p class="error">'. $errorMessage . '</p>' . $this->content;
  }

  /**
   * Adds message text to content
   *
   * @param string $message
   * @return  void
   */
  protected function addMessage($message = '')
  {
    $this->content .= '<p class="message">'. $message . '</p>';
  }

  /**
   * Adds message text to the top of the content
   *
   * @param string $message
   * @return  void
   */
  protected function addMessageToTop($message = '')
  {
    $this->content = '<p class="message">'. $message . '</p>' . $this->content;
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
   * @return Doctrine\ORM\EntityManager
   */
  protected function getEM($applicationPath, $dbName = '', $new = false, $pdo = null)
  {
    if ($new) {
      $this->newEm = EntityManager::getEntityManager($applicationPath, $pdo, $dbName);
      return $this->newEm;
    }
    if (!isset($this->em)) {
      $this->em = EntityManager::getEntityManager($applicationPath, $pdo, $dbName);
    }
    return $this->em;
  }

  /**
   * Gets the new entity manager generated in getEm with $new = true. Or generates a new one itself if a new one hasn't been set yet.
   *
   * @param  string $applicationPath full path to the application
   * @param  string $dbName          name of the database in the config file
   * @param  \PDO    $pdo            Optional pre-existing connection to use
   * @return Doctrine\ORM\EntityManager
   */
  protected function getNewEM($applicationPath = '', $dbName = '', $pdo = null)
  {
    if (!isset($this->newEm)) {
      $this->newEm = EntityManager::getEntityManager($applicationPath, $pdo, $dbName);
    }
    return $this->newEm;
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
   * @return  void
   */
  protected function setSessionMessage($message = '', $isError = false, $path = null)
  {
    if ($path === null) {
      $path = isset($_SERVER['REQUEST_URI']) ? $_SERVER['REQUEST_URI'] : null;
    }
    PageUtil::setSessionMessage($message, $isError, $path);
  }

  /**
   * Adds session messages to the page if any are set
   *
   * @return void
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
   * Gets the version for the application
   *
   * @return string
   */
  protected function getApplicationVersion()
  {
    return '0.0.0';
  }

  /**
   * Checks to see if we have a form to restore to return. If not, we prepare
   * Builds a form using FormBuilder and adds BotLure to the form.
   *
   * @param  string   $formKey               Key the form uses for saving and submiting
   * @param  callable|array $configuration   Callback used to get the configuration array if needed, or the configuration array itself.
   *     <strong>Note:</strong> Passing a callable is recommended
   * @param  array    $callableParameters    Parameters to pass onto the callable
   * @param  mixed    $version  Version of the form
   *
   * @throws  InvalidArgumentException If $configuration is not an array or a callable
   * @return FormBuilder
   */
  protected function buildForm($formKey, $configuration, $callableParameters = null, $version = null)
  {
    if ($version === null) {
      $version = $this->getApplicationVersion();
    }
    $form = $this->restoreForm($formKey, $version);
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
      $form = $this->prepareForm($config, $formKey, $version);
      // restore form in case we have post-data to populate with
      $form = $this->restoreForm($formKey, $version);
    }
    return $form;
  }

  /**
   * Prepares a form using FormBuilder and adds ButLure to it
   *
   * @param  array   $config   Configuration array to build a form from
   * @param  string  $formKey  Key of the form
   * @param  mixed   $version  Version of the form
   * @param  mixed   $ttl      Amount of time the form is kept around
   * @param  boolean $serialize Whether to serialize the form before storing it or not
   * @return  FormBuilder The prepared form
   */
  protected function prepareForm($config, $formKey = null, $version = null, $ttl = null, $serialize = false)
  {
    if ($version === null) {
      $version = $this->getApplicationVersion();
    }
    $form = FormBuilder::prepareForm($config, $formKey, $version, $ttl, $serialize);
    // add the botLure to attempt to keep bots from submitting the form.
    $form->addChildren(new BotLure);
    return $form;
  }

  /**
   * Restores a form from FormBuilder
   *
   * @param  string $formKey Key of the form to restore
   * @param  mixed  $version Version of the form
   * @param  mixed  $ttl     Amount of time the form is kept around
   * @return FormBuilder
   */
  protected function restoreForm($formKey = null, $version = null, $ttl = null)
  {
    if ($version === null) {
      $version = $this->getApplicationVersion();
    }
    return FormBuilder::restoreForm($formKey, $version, $ttl);
  }
}