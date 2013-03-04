<?php

namespace Gustavus\Concourse\Test;

use Gustavus\Concourse\Controller;

class ControllerTestController extends Controller
{
  /**
   * test apiKey
   * @var string
   */
  protected $apiKey = '5399382D942ED2F4138663E18FD6D558';

  /**
   * {@inheritdoc}
   */
  public function getLocalNavigation()
  {
    return array();
  }

  /**
   * {@inheritdoc}
   */
  public function getRoutingConfiguration()
  {
    return '/cis/lib/Gustavus/Concourse/Test/routing.php';
  }

  /**
   * overloads renderPage for testing so we don't try to render the page
   *
   * @return array
   */
  protected function renderPage()
  {
    $this->addSessionMessages();
    return [
      'title'           => $this->getTitle(),
      'subtitle'        => $this->getSubtitle(),
      'content'         => $this->getContent(),
      'localNavigation' => $this->getLocalNavigation(),
      'focusBox'        => $this->getFocusBox(),
      'stylesheets'     => $this->getStylesheets(),
      'javascripts'     => $this->getJavascripts(),
    ];
  }

  /**
   * overloads redirect for testing so we don't try to redirect
   *
   * @param  string $path
   * @return void
   */
  protected function redirect($path = '/')
  {
    $_POST = null;
  }
}