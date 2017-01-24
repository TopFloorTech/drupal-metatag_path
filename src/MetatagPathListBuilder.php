<?php

namespace Drupal\metatag_path;

use Drupal\Core\Config\Entity\ConfigEntityListBuilder;
use Drupal\Core\Entity\EntityInterface;
use Drupal\corresponding_reference\Entity\CorrespondingReferenceInterface;

class MetatagPathListBuilder extends ConfigEntityListBuilder {
  public function buildHeader() {
    $header = [
      'label' => $this->t('Label'),
      'id' => $this->t('Machine name'),
      'fields' => $this->t('Corresponding fields'),
      'enabled' => $this->t('Enabled'),
    ];

    return $header + parent::buildHeader();
  }

  public function buildRow(EntityInterface $entity) {
    /** @var CorrespondingReferenceInterface $entity */

    $row = [
      'label' => $entity->label(),
      'id' => $entity->id(),
      'fields' => $this->getCorrespondingFields($entity),
      'enabled' => $entity->isEnabled() ? $this->t('Yes') : $this->t('No'),
    ];

    return $row + parent::buildRow($entity);
  }

  protected function getCorrespondingFields(CorrespondingReferenceInterface $entity) {
    $fields = $entity->getCorrespondingFields();

    $items = [];

    foreach ($fields as $field) {
      $items[] = $field;
    }

    return \Drupal::theme()->render('item_list', ['items' => $items]);
  }
}
