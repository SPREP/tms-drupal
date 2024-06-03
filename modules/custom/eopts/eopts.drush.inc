<?php

/**
 * @file
 * Contains custom drush commands.
 */

/**
 * Implements hook_drush_command().
 */

use Drupal\eopts\Commands\DebundlerDrushCommand;

/**
 * The eopts debundler.
 */
function eopts_drush_command() {
  $items = [];

  $items['eopts_debundle'] = [
    'description' => 'Perform debundling.',
    'arguments' => [
      'mode' => 'Mode like csv, md or kint',
    ],
    'default' => 'csv',
    'examples' => [
      'drush deb md' => 'Exports all entity information in a markdown format',
      'drush deb csv' => 'Exports all entity information in a csv format (Lucid charts)',
      'drush deb kint' => 'Exports all entity information in a HTML format (kint style)',
    ],
    'aliases' => ['eopts-debundle', 'deb'],
  ];

  return $items;
}

/**
 * The eopts debundler.
 */
function drush_eopts_debundle($mode) {
  $command = new DebundlerDrushCommand();
  $command->eoptsExport($mode);
}
