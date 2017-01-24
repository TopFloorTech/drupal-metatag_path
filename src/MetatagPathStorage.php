<?php

namespace Drupal\metatag_path;
use Drupal\Core\Config\Entity\ConfigEntityStorage;
use Drupal\Core\Entity\EntityInterface;

/**
 * Defines the tax service storage.
 */
class MetatagPathStorage extends ConfigEntityStorage implements MetatagPathStorageInterface {

  /**
   * {@inheritdoc}
   */
  public function loadValid(EntityInterface $entity) {
    $query = $this->getQuery()
      ->condition('enabled', 1);

    $result = $query->execute();

    if (empty($result)) {
      return [];
    }

    return $this->loadMultiple($result);
  }
}
