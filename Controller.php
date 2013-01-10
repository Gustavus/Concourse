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
  Campus\Pull\People;

/**
 * Shared controller for all Concourse applications
 *
 * @package Concourse
 * @author  Billy Visto
 */
abstract class Controller
{
  /**
   * Contains the default Campus API key for all Symfony applications.
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
  protected $templatePreferences = [];

  /**
   * Doctrine\ORM\EntityManager
   * @var Doctrine\ORM\EntityManager
   */
  protected static $em;

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
   * @return $this to enable method chaining
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
   * @return $this to enable method chaining
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
   * @return $this to enable method chaining
   */
  protected function setContent($content)
  {
    $this->content = $content;
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
   * @return $this to enable method chaining
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
   * @return $this to enable method chaining
   */
  protected function setStylesheets($stylesheets)
  {
    $this->stylesheets = $stylesheets;
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
   * @return $this to enable method chaining
   */
  protected function setJavascripts($javascripts)
  {
    $this->javascripts = $javascripts;
    return $this;
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
   * @return $this to enable method chaining
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
   * @return $this to enable method chaining
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
   * @return $this to enable method chaining
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
    $args = [
      'title'           => $this->getTitle(),
      'subtitle'        => $this->getSubtitle(),
      'content'         => $this->getContent(),
      'localNavigation' => $this->getLocalNavigation(),
      'focusBox'        => $this->getFocusBox(),
      'stylesheets'     => $this->getStylesheets(),
      'javascripts'     => $this->getJavascripts(),
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
    $this->setContent(TwigFactory::renderTwigFilesystemTemplate($view, $parameters, \Config::isBeta()));
    return $this->renderPage();
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
    $person = $this->getLoggedInPerson();
    return ($person !== null) ? $person->getUsername() : null;
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
   * @return Person|null returns the logged in Person if found. null otherwise
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
   * @return Doctrine\ORM\EntityManager
   */
  protected function getEM($applicationPath, $dbName = '')
  {
    if (!isset(self::$em)) {
      self::$em = EntityManager::getEntityManager($applicationPath, null, $dbName);
    }
    return self::$em;
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
}