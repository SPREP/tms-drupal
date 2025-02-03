<?php

declare(strict_types=1);

namespace Drupal\met_warning;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityListBuilder;

/**
 * Provides a list controller for the met warning entity type.
 */
final class METWarningListBuilder extends EntityListBuilder {

  /**
   * {@inheritdoc}
   */
  public function buildHeader(): array {
    $header['id'] = $this->t('ID');
    $header['title'] = $this->t('Title');
    $header['status'] = $this->t('Status');
    $header['uid'] = $this->t('Author');
    $header['created'] = $this->t('Created');
    $header['changed'] = $this->t('Updated');
    return $header + parent::buildHeader();
  }

  /**
   * {@inheritdoc}
   */
  public function buildRow(EntityInterface $entity): array {
    /** @var \Drupal\met_warning\METwarningInterface $entity */
    $row['id'] = $entity->id();
    $row['title'] = $entity->toLink();
    $row['status'] = $entity->get('status')->value ? $this->t('Enabled') : $this->t('Disabled');
    $username_options = [
      'title' => 'hidden',
      'settings' => ['link' => $entity->get('uid')->entity->isAuthenticated()],
    ];
    $row['uid']['data'] = $entity->get('uid')->view($username_options);
    $row['created']['data'] = $entity->get('created')->view(['title' => 'hidden']);
    $row['changed']['data'] = $entity->get('changed')->view(['title' => 'hidden']);
    return $row + parent::buildRow($entity);
  }

}
