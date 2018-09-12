<?php

namespace ImageStyles\Controller;

use \Cockpit\AuthController;

/**
 * Admin controller class.
 */
class Admin extends AuthController {

  /**
   * Default index controller.
   */
  public function index() {
    if (!$this->app->module('cockpit')->hasaccess('imagestyles', 'manage.view')) {
      return FALSE;
    }

    $styles = $this->module("imagestyles")->styles(TRUE);

    return $this->render('imagestyles:views/styles/index.php', [
      'styles' => $styles,
    ]);
  }

  /**
   * Style controller.
   */
  public function style($name = NULL) {
    if (!$this->app->module('cockpit')->hasaccess('imagestyles', 'manage.admin')) {
      return FALSE;
    }

    $style = [
      'name' => '',
      'description' => '',
      'mode' => 'thumbnail',
      'width' => '128',
      'height' => '128',
      'anchor' => 'center',
      'quality' => 90,
      'base64' => FALSE,
      'domain' => FALSE,
      'effects' => [],
    ];

    if ($name) {
      if (!$style = $this->module('imagestyles')->style($name)) {
        return FALSE;
      }
    }

    return $this->render('imagestyles:views/styles/style.php', compact('style'));
  }

}
