<?php

/**
 * @file
 * Cockpit ImageStyles admin functions.
 */

// Module ACL definitions.
$this("acl")->addResource('imagestyles', [
  'manage.view',
  'manage.admin',
  'rebuild',
]);

/*
 * add menu entry if the user has access to group stuff
 */
$this->on('cockpit.view.settings.item', function () {
  if ($this->module('cockpit')->hasaccess('imagestyles', 'manage.view')) {
     $this->renderView("imagestyles:views/partials/settings.php");
  }
});

$app->on('admin.init', function () use ($app) {
  // Bind admin routes /image-styles.
  $this->bindClass('ImageStyles\\Controller\\Admin', 'image-styles');
  // Add effects manager field.
  $this->helper('admin')->addAssets('imagestyles:assets/cp-effectsmanager.tag');
});

// Dashboard widgets.
$this->on("admin.dashboard.widgets", function ($widgets) {

  $imagestyles = $this->module("imagestyles")->styles(TRUE);

  $widgets[] = [
    "name"    => "imagestyles",
    "content" => $this->view("imagestyles:views/widgets/dashboard.php", compact('imagestyles')),
    "area"    => 'aside-right',
  ];

}, 100);


$this->on('collections.entry.aside', function() {
  $this->renderView("imagestyles:views/partials/entry-aside.php");
});