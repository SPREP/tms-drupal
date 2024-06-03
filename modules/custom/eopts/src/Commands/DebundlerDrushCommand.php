<?php

namespace Drupal\eopts\Commands;

use Drupal\Core\Config\Entity\ConfigEntityTypeInterface;
use Drupal\Core\Entity\FieldableEntityInterface;
use Drush\Commands\DrushCommands;

/**
 * Migrate Tools drush commands.
 */
class DebundlerDrushCommand extends DrushCommands {

  /**
   * The output string.
   *
   * @var string
   */
  protected $outputStr;

  /**
   * Perform debundling.
   *
   * @param string $mode
   *   Debundling mode.
   *
   * @command eopts:debundle
   *
   * @default $mode md
   *
   * @usage drush deb md
   *   Exports all entity information in a markdown format
   * @usage drush deb csv
   *   Exports all entity information in a csv format (Lucid charts)
   * @usage drush deb kint
   *   Exports all entity information in a HTML format (kint style)
   *
   * @aliases eopts-debundle,deb
   *
   * @throws \Exception
   *   If there are not enough parameters to the command.
   */
  public function eoptsExport($mode) {
    $data = '';
    $destination = '/tmp/';
    $this->println("Debundling mode [$mode]");

    $sitename = \Drupal::config('system.site')->get('name');
    if (!$sitename) {
      $sitename = 'output';
    }
    $sitename = str_replace(' ', '_', $sitename);

    switch ($mode) {
      case 'md':
        $data = $this->outputMarkdown($this->getContentEntities());
        $destination = "/tmp/$sitename.md";
        break;

      case 'pdf':
        // @todo fix pdf here.
        break;

      case 'csv':
        $destination = "/tmp/$sitename.csv";
        $data = $this->outputLucid($this->getContentEntities());
        break;

      case 'kint':
        $destination = "/tmp/$sitename.html";
        $data = $this->kintPrintToHtml($this->getConfigEntities());
        break;

      default:
        $message = "Please choose one of the following modes [md, csv (lucid) or kint (html)]\r\n"
          . "Example: drush deb csv\r\n";
        throw new \Exception(dt($message));
    }

    file_put_contents($destination, $data);
    $this->println("Saved to $destination");
  }

  /**
   * The hello world.
   */
  protected function debundle() {
    return "Hello world";
  }

  /**
   * Get all content entities.
   *
   * @return array
   *   Assoc array of entities.
   */
  protected function getContentEntities() {
    $entities = [];
    foreach ($this->getAllEntities() as $name => $entity) {
      if ($entity['group'] === 'content') {
        $entities[$name] = $entity;
      }
    }
    return $entities;
  }

  /**
   * Get all configuration entities.
   *
   * @return array
   *   Assoc array of entities.
   */
  protected function getConfigEntities() {
    $entities = [];
    foreach ($this->getAllEntities() as $name => $entity) {
      if ($entity['group'] === 'configuration') {
        $entities[$name] = $entity;
      }
    }
    return $entities;
  }

  /**
   * Borrowed from Drupal\erd\Controller\EntityRelationshipDiagramController.
   *
   * From drupal.org/project/erd.
   *
   * @return array
   *   Assoc array of entities.
   */
  protected function getAllEntities() {
    // Getting a Drupal container.
    $container = \Drupal::getContainer();

    $entity_definitions = $container->get('entity_type.manager')
      ->getDefinitions();
    $entities = [];
    $links = [];

    foreach ($entity_definitions as $definition) {
      $entity = [
        'id' => $definition->id(),
        'type' => 'type',
        'type_label' => t('Entity Type'),
        'label' => $definition->getLabel(),
        'provider' => $definition->getProvider(),
        'group' => $definition->getGroup(),
        'bundles' => [],
      ];

      if ($definition instanceof ConfigEntityTypeInterface) {
        $entity['config_properties'] = $definition->getPropertiesToExport();
      }

      $bundles = $container->get('entity_type.bundle.info')
        ->getBundleInfo($definition->id());
      foreach ($bundles as $bundle_id => $bundle_label) {
        $bundle = [
          'id' => $bundle_id,
          'type' => 'bundle',
          'type_label' => t('Entity Bundle'),
          'label' => $bundle_label['label'],
          'entity_type' => $definition->id(),
        ];

        if ($definition->isSubclassOf(FieldableEntityInterface::class)) {
          $bundle['fields'] = [];
          /** @var \Drupal\Core\Entity\EntityFieldManager $entity_field_manager */
          $entity_field_manager = $container->get('entity_field.manager');
          $fields = $entity_field_manager->getFieldDefinitions($definition->id(), $bundle_id);
          foreach ($fields as $field) {
            /** @var \Drupal\Core\Field\BaseFieldDefinition $field_storage_definition */
            $field_storage_definition = $field->getFieldStorageDefinition();
            $field_settings = $field->getItemDefinition()->getSettings();
            $type_length = $this->getSettingLength($field_storage_definition->getType(), $field_settings);

            $field_name = $field_storage_definition->getName();
            $bundle['fields'][$field_name] = [
              'id' => $field_name,
              'label' => $field->getLabel(),
              'type' => $field_storage_definition->getType(),
              'type_length' => $type_length,
              'description' => $field_storage_definition->getDescription(),
              'cardinality' => $field_storage_definition->getCardinality(),
              'is_multiple' => $field_storage_definition->isMultiple(),
            ];
            $types[$field_storage_definition->getType()] = $field_storage_definition->getType();
            $link = [];
            if ($bundle['fields'][$field_name]['type'] == 'entity_reference') {
              $link = [
                'label' => t('Entity Reference from field "@field_name"', [
                  '@field_name' => $field_name,
                ]),
                'from' => 'bundle:' . $bundle_id,
                'from_selector' => '.attribute-background-' . $field_name,
                'targets' => ['type:' . $field_settings['target_type']],
              ];

              if (isset($field_settings['handler_settings']['target_bundles'])) {
                foreach ($field_settings['handler_settings']['target_bundles'] as $target_bundle) {
                  $link['targets'][] = 'bundle:' . $target_bundle;
                }
              }

              $links[] = $link;
            }
            else {
              if ($bundle['fields'][$field_name]['type'] == 'image') {
                $link = [
                  'label' => t('Image Reference from field "@field_name"', [
                    '@field_name' => $field_name,
                  ]),
                  'from' => 'bundle:' . $bundle_id,
                  'from_selector' => '.attribute-background-' . $field_name,
                  'targets' => ['type:' . $field_settings['target_type']],
                ];
                $links[] = $link;
              }
            }
            $bundle['fields'][$field_name]['link'] = $link;
          }
        }

        $entity['bundles'][$bundle_id] = $bundle;
      }

      $entities[$definition->id()] = $entity;
    }

    return $entities;
  }

  /**
   * Helper function to assume a setting field length.
   *
   * There maybe a better way to do this.
   *
   * @param string $field_type
   *   The type of the field ie integer.
   * @param array $settings
   *   Settings returned by Drupal.
   *
   * @return mixed|string
   *   Return the length.
   */
  protected function getSettingLength(string $field_type, array $settings) {
    if (isset($settings['max_length'])) {
      return $settings['max_length'];
    }

    switch ($field_type) {
      case 'boolean':
        return '4';

      case 'integer':
      case 'entity_reference':
      case 'entity_reference_snapshot':
      case 'list_integer':
        return '10';

      case 'created':
      case 'changed':
      case 'timestamp':
        return '11';

      case 'language':
        return '12';

      case 'email':
      case 'list_string':
        return '255';

      case 'path':
      case 'link':
        return '2048';

      case 'string_long':
      case 'text_long':
      case 'text_with_summary':
      case 'file':
      case 'image':
      case 'fish_caught_weight':
      case 'geofield':
        return '0';

      case 'datetime':
        return '20';

      case 'decimal':
      case 'float':
        return '10.4';
    }

  }

  /**
   * Outputs in a mark down format.
   *
   * @param array $entity_types
   *   An array of entity types.
   */
  protected function outputMarkdown(array $entity_types) {
    $sitename = \Drupal::config('system.site')->get('name');
    $this->appendMarkdown("# $sitename Entities");
    $bundle_head_level = '###';
    foreach ($entity_types as $entity_type) {
      $id = $entity_type['id'];
      /** @var \Drupal\Core\StringTranslation\TranslatableMarkup $label */
      $label = $entity_type['label'];
      $this->appendMarkdown("## {$label} ($id)");
      $this->appendMarkdown("### Common fields for all *$id* type entities");

      $common_fields = [];
      foreach ($entity_type['bundles'] as $bundle_name => $bundle) {
        foreach ($bundle['fields'] as $field) {
          // Assume that any field that deson't start with field_ is property.
          if (!(strpos($field['id'], 'field_') === 0)) {
            $common_fields[$field['id']] = $field;
          }
        }
      }

      $rows = [];
      $rows[] = '| field_name |     type      |  description  |';
      $rows[] = '|------------|---------------|---------------|';
      foreach ($common_fields as $common_field) {
        $rows[] = "|{$common_field['id']}|{$common_field['type']}|{$common_field['description']}|";
      }

      $table = implode("\n", $rows);
      $this->appendMarkdown($table);
      $this->appendMarkdown("### All *$id* type entities");

      foreach ($entity_type['bundles'] as $bundle_name => $bundle) {
        $rows = [];
        // Link to content type here.
        $rows[] = "$bundle_head_level $bundle_name";
        $rows[] = "| label | field_name | type | allowed_values | cardinality |fk from|fk to|";
        $rows[] = "|-------|------------|------|----------------|-------------|-------|-----|";

        foreach ($bundle['fields'] as $field) {
          // If field doesn't start with "field_" , then skip.
          if (!(strpos($field['id'], 'field_') === 0)) {
            continue;
          }
          // Not empty $field['link'] means its an entity reference.
          if (!empty($field['link'])) {
            $from = $field['link']['from'];
            $to = implode('|', $field['link']['targets']);
          }
          $rows[] = "|**{$field['label']}**|{$field['id']}|" .
            "{$field['type']}|*allowed_values*|" .
            "{$field['cardinality']}|{$from}|{$to}|";
        }

        if (count($rows) === 3) {
          $rows = [];
          $rows[] = "$bundle_head_level $bundle_name";
          $rows[] = "`There is no additional fields for ($bundle_name) entity.`";
        }
        $table = implode("\n", $rows);
        $this->appendMarkdown($table);
      }
    }
    return $this->outputStr;
  }

  /**
   * Outputs a CSV format where it is accepted to be imported by Lucid charts.
   *
   * Lucid charts ED import style CSV (Mysql import) row should have:
   * 'mysql' dbms
   * TABLE_SCHEMA
   * TABLE_NAME
   * COLUMN_NAME
   * ORDINAL_POSITION
   * DATA_TYPE
   * CHARACTER_MAXIMUM_LENGTH
   * CONSTRAINT_TYPE
   * REFERENCED_TABLE_SCHEMA
   * REFERENCED_TABLE_NAME
   * REFERENCED_COLUMN_NAME.
   *
   * @param array $entity_types
   *   An array of entity types.
   */
  protected function outputLucid(array $entity_types) {
    // Loop over entity types.
    foreach ($entity_types as $entity_type_id => $entity_type) {
      $this->appendLine("\n######### $entity_type_id\n", TRUE);
      $row_base = ['mysql', 'drupal'];
      $common_fields = [];
      foreach ($entity_type['bundles'] as $bundle_name => $bundle) {
        $ignore_entity_types_list = [
          "block_content",
        ];
        $ignore_list_bundles = [
          'paragraph:image_inline_right',
        ];
        $ordinality = 1;
        if (in_array($entity_type_id, $ignore_entity_types_list)) {
          continue;
        }
        if (in_array("$entity_type_id:$bundle_name", $ignore_list_bundles)) {
          continue;
        }
        $row_start = array_merge($row_base, ["$entity_type_id:$bundle_name"]);
        $this->appendLine(implode("\t", array_merge($row_start, [
          'entity_type',
          $ordinality,
          "$entity_type_id",
          'NULL',
          'NULL',
          'NULL',
          'NULL',
          'NULL',
        ])));
        $this->appendLine("\n");
        $ordinality += 1;
        $other_fields = [];
        foreach ($bundle['fields'] as $field) {
          // Assume that any field that deson't start with field_ is property.
          if (!(strpos($field['id'], 'field_') === 0)) {
            $common_fields[$field['id']] = $field;
          }
          else {
            $other_fields[$field['id']] = $field;
          }
        }

        foreach ($other_fields as $other_field_id => $other_field) {
          $row = $row_start;
          $row[] = $other_field_id;
          // $displayed_fields[] = $field_title;.
          $row[] = $ordinality;
          $row[] = $other_field['type'];
          $row[] = $other_field['type_length'];
          // Not empty $field['link'] means its an entity reference.
          $this->findLinks($row, $other_field);
          $ordinality += 1;
          $this->appendLine(implode("\t", $row));
          $this->appendLine("\n");
        }

        // The code of comment fields.
        if (count($common_fields) > 0) {
          $this->appendLine(implode("\t", array_merge($row_start, [
            '—————————————————',
            $ordinality,
            '—',
            'NULL',
            'NULL',
            'NULL',
            'NULL',
            'NULL',
          ])));
          $this->appendLine("\n");
          $ordinality += 1;
          foreach ($common_fields as $field_name => $field) {
            $row = ['mysql', 'drupal', "$entity_type_id:$bundle_name"];
            $row[] = $field_name;
            $row[] = $ordinality;
            $row[] = $field['type'];
            $row[] = $field['type_length'];
            $this->findLinks($row, $field);
            $this->appendLine(implode("\t", $row));
            $this->appendLine("\n");
            $ordinality += 1;
          }
        }

      }
    }
    return $this->outputStr;
  }

  /**
   * Helper function to determine a Foreign key. There might be a better way.
   */
  protected function targetId($entity_type) {
    $id = $entity_type;
    switch ($entity_type) {
      case 'node':
        $id = 'nid';
        break;

      case 'taxonomy_term':
        $id = 'tid';
        break;

      case 'file':
        $id = 'fid';
        break;

      case 'user':
        $id = 'uid';
        break;
    }
    return $id;
  }

  /**
   * Helper function to parse an array for entity references links.
   */
  protected function findLinks(&$row, array $field) {
    if (!empty($field['link'])) {
      $row[] = 'FOREIGN KEY';
      $row[] = 'drupal';

      $link_targets = $field['link']['targets'];
      $target_entity_type = $link_targets[0];
      $target_entity_type = explode(':', $target_entity_type);
      $target_entity_type = $target_entity_type[1];

      $target_entity = $link_targets[1];
      if ($target_entity) {
        $target_entity = explode(':', $target_entity);
        $target_entity = $target_entity[1];
      }
      else {
        $target_entity = $target_entity_type;
      }

      $row[] = "$target_entity_type:$target_entity";
      $row[] = $this->targetId($target_entity_type);
    }
    else {
      $row[] = 'NULL';
      $row[] = 'NULL';
      $row[] = 'NULL';
      $row[] = 'NULL';
    }
  }

  /**
   * Helper function to append a line to a string output.
   *
   * @param string $line
   *   Line string.
   * @param bool $return
   *   Not needed now.
   */
  protected function appendLine($line, $return = TRUE) {
    $this->outputStr .= $line;
  }

  /**
   * Helper function to println to a standard output.
   */
  protected function println($line) {
    print_r("$line\n");
  }

  /**
   * Helper function to print markdown to a standard output.
   */
  protected function printMarkdown($md) {
    print_r("\n$md\n\n");
  }

  /**
   * Helper function to construct a markdown text.
   */
  protected function appendMarkdown($md) {
    $this->outputStr .= "\n$md\n\n";
  }

  /**
   * Alias of Kint::dump(), print to standard output.
   *
   * Prints passed argument(s) using Kint debug tool.
   */
  protected function kintPrintToStdOutput() {
    kint_require();
    $args = func_get_args();
    \Kint::dump($args);
  }

  /**
   * Helper function to output a kint like HTML output to a text then a file.
   */
  protected function kintPrintToHtml() {
    $moduleHandler = \Drupal::service('module_handler');
    if (!$moduleHandler->moduleExists('kint')) {
      throw new \Exception(dt("Please enable 'kint' module first. Run `drush en kint`"));
    }
    kint_require();
    \Kint::enabled(\Kint::MODE_RICH);
    $args = func_get_args();
    $data = \Kint::dump($args);
    return $data;
  }

  /**
   * Public wrapper to kint.
   *
   * @param mixed $data
   *   Mixed data to output.
   * @param string $destination
   *   Full path to the output file.
   *
   * @throws \Exception
   */
  public function kint($data, $destination = '') {
    // @todo added it for testing, should be deleted later.
    // Either we add it to eopts template module or just ignore it
    if (!$destination) {
      $destination = 'kint.html';
    }
    else {
      $destination .= '/kint.html';
    }
    $result = $this->kintPrintToHtml($data);
    file_put_contents($destination, $result, FILE_APPEND | LOCK_EX);
  }

}
