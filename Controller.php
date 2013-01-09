<?php
/**
 * @package Concourse
 * @author  Billy Visto
 */

namespace Gustavus\Concourse;
use Gustavus\TemplateBuilder\Builder as TemplateBuilder;

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
   * Returns the local navigation array to be supplied to the Item Factory in $this->renderLocalNavigation()
   *
   * @return array a valid array to be passed into the ItemFactory class
   */
  abstract protected function getLocalNavigation();

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
}