<?php

namespace Drupal\metatag_path\Entity;

use Drupal\Core\Config\Entity\ConfigEntityBase;
use Drupal\Core\Entity\FieldableEntityInterface;
use Drupal\Core\Field\FieldItemInterface;
use Drupal\corresponding_reference\CorrespondingReferenceOperations;

/**
 * Defines a corresponding reference entity.
 *
 * @ConfigEntityType(
 *   id = "corresponding_reference",
 *   label = @Translation("Corresponding reference"),
 *   handlers = {
 *     "list_builder" = "Drupal\corresponding_reference\CorrespondingReferenceListBuilder",
 *     "storage" = "Drupal\corresponding_reference\CorrespondingReferenceStorage",
 *     "form" = {
 *       "add" = "Drupal\corresponding_reference\Form\CorrespondingReferenceForm",
 *       "edit" = "Drupal\corresponding_reference\Form\CorrespondingReferenceForm",
 *       "delete" = "Drupal\corresponding_reference\Form\CorrespondingReferenceDeleteForm",
 *       "sync" = "Drupal\corresponding_reference\Form\CorrespondingReferenceSyncForm",
 *     }
 *   },
 *   config_prefix = "corresponding_reference",
 *   admin_permission = "administer corresponding_reference",
 *   entity_keys = {
 *     "id" = "id",
 *     "label" = "label"
 *   },
 *   links = {
 *     "collection" = "/admin/config/content/corresponding_reference",
 *     "edit-form" = "/admin/config/content/corresponding_reference/{corresponding_reference}",
 *     "delete-form" = "/admin/config/content/corresponding_reference/{corresponding_reference}/delete",
 *     "sync-form" = "/admin/config/content/corresponding_reference/{corresponding_reference}/sync"
 *   }
 * )
 */
class MetatagPath extends ConfigEntityBase implements CorrespondingReferenceInterface {

  /**
   * The corresponding reference machine name.
   *
   * @var string
   */
  public $id;

  /**
   * The corresponding reference label.
   *
   * @var string
   */
  public $label;

  /**
   * The first corresponding field ID.
   *
   * @var string
   */
  public $first_field;

  /**
   * The second corresponding field ID.
   *
   * @var string
   */
  public $second_field;

  /**
   * The corresponding bundles keyed by entity type.
   *
   * Example:
   *   [
   *     'node' => ['article', 'page'],
   *     'commerce_product' => ['default']
   *   ]
   *
   * @var array
   */
  public $bundles;

  /**
   * Whether or not this corresponding reference is enabled.
   *
   * @var bool
   */
  public $enabled;

  /**
   * {@inheritdoc}
   */
  public function id() {
    return $this->id;
  }

  /**
   * {@inheritdoc}
   */
  public function getId() {
    return $this->id;
  }

  /**
   * {@inheritdoc}
   */
  public function setId($id) {
    $this->id = $id;

    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getLabel() {
    return $this->label;
  }

  /**
   * {@inheritdoc}
   */
  public function setLabel($label) {
    $this->label = $label;

    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getFirstField() {
    return $this->first_field;
  }

  /**
   * {@inheritdoc}
   */
  public function setFirstField($firstField) {
    $this->first_field = $firstField;

    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getSecondField() {
    return $this->second_field;
  }

  /**
   * {@inheritdoc}
   */
  public function setSecondField($secondFIeld) {
    $this->second_field = $secondFIeld;

    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getBundles() {
    return $this->bundles;
  }

  /**
   * {@inheritdoc}
   */
  public function setBundles(array $bundles) {
    $this->bundles = $bundles;

    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function isEnabled() {
    return $this->enabled;
  }

  /**
   * {@inheritdoc}
   */
  public function setEnabled($enabled) {
    $this->enabled = $enabled;

    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getCorrespondingFields() {
    $first = $this->getFirstField();
    $second = $this->getSecondField();

    $correspondingFields = [];

    if (!empty($first)) {
      $correspondingFields[$first] = $first;
    }

    if (!empty($second)) {
      $correspondingFields[$second] = $second;
    }

    return $correspondingFields;
  }

  /**
   * {@inheritdoc}
   */
  public function hasCorrespondingFields(FieldableEntityInterface $entity) {
    $hasCorrespondingFields = FALSE;

    foreach ($this->getCorrespondingFields() as $field) {
      if ($entity->hasField($field)) {
        $hasCorrespondingFields = TRUE;

        break;
      }
    }

    return $hasCorrespondingFields;
  }

  /**
   * {@inheritdoc}
   */
  public function synchronizeCorrespondingFields(FieldableEntityInterface $entity) {
    if (!$this->isValid($entity)) {
      return;
    }

    foreach ($this->getCorrespondingFields() as $fieldName) {
      if (!$entity->hasField($fieldName)) {
        continue;
      }

      $differences = $this->calculateDifferences($entity, $fieldName);
      $correspondingField = $this->getCorrespondingField($fieldName);

      foreach ($differences as $operation => $entities) {
        /** @var FieldableEntityInterface $correspondingEntity */
        foreach ($entities as $correspondingEntity) {
          $this->synchronizeCorrespondingField($entity, $correspondingEntity, $correspondingField, $operation);
        }
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function isValid(FieldableEntityInterface $entity) {
    $bundles = $this->getBundles();
    $entityTypes = array_keys($bundles);
    $entityType = $entity->getEntityTypeId();

    if (!in_array($entityType, $entityTypes)) {
      return FALSE;
    }

    if (!in_array($entity->bundle(), $bundles[$entityType]) && !in_array('*', $bundles[$entityType])) {
      return FALSE;
    }

    if (!$this->hasCorrespondingFields($entity)) {
      return FALSE;
    }

    return TRUE;
  }

  /**
   * Gets the name of the corresponding field of the provided field.
   *
   * @param $fieldName string
   *   The provided field name.
   *
   * @return string
   *   The corresponding field name.
   */
  public function getCorrespondingField($fieldName) {
    $fields = $this->getCorrespondingFields();

    if (count($fields) == 1) {
      return $fieldName;
    }

    unset($fields[$fieldName]);

    return array_shift($fields);
  }

  /**
   * {@inheritdoc}
   */
  public function synchronizeCorrespondingField(FieldableEntityInterface $entity, FieldableEntityInterface $correspondingEntity, $correspondingFieldName, $operation = NULL) {
    if (is_null($operation)) {
      $operation = CorrespondingReferenceOperations::ADD;
    }

    if (!$correspondingEntity->hasField($correspondingFieldName)) {
      return;
    }

    $field = $correspondingEntity->get($correspondingFieldName);

    $values = $field->getValue();

    $index = NULL;

    foreach ($values as $idx => $value) {
      if ($value['target_id'] == $entity->id()) {
        if ($operation == CorrespondingReferenceOperations::ADD) {
          return;
        }

        $index = $idx;
      }
    }

    $set = FALSE;

    switch ($operation) {
      case CorrespondingReferenceOperations::REMOVE:
        if (!is_null($index)) {
          unset($values[$index]);
          $set = TRUE;
        }
        break;
      case CorrespondingReferenceOperations::ADD:
        $values[] = ['target_id' => $entity->id()];
        $set = TRUE;
        break;
    }

    if ($set) {
      drupal_set_message(sprintf(
        '%s corresponding record(s) on entity %s',
        ($operation == CorrespondingReferenceOperations::REMOVE ? 'Removed' : 'Added'),
        $correspondingEntity->label()
      ));

      $field->setValue($values);
      $correspondingEntity->save();
    }
  }

  /**
   * Return added and removed entities from the provided field.
   *
   * @param \Drupal\Core\Entity\FieldableEntityInterface $entity
   *   The current entity.
   *
   * @param $fieldName
   *
   * @return array
   *   The differences keyed by 'added' and 'removed'.
   */
  protected function calculateDifferences(FieldableEntityInterface $entity, $fieldName) {
    /** @var FieldableEntityInterface $original */
    $original = isset($entity->original) ? $entity->original : NULL;

    $differences = [
      CorrespondingReferenceOperations::ADD => [],
      CorrespondingReferenceOperations::REMOVE => [],
    ];

    if (!$entity->hasField($fieldName)) {
      return $differences;
    }

    $entityField = $entity->get($fieldName);

    if (empty($original)) {
      /** @var FieldItemInterface $fieldItem */
      foreach ($entityField as $fieldItem) {
        $differences[CorrespondingReferenceOperations::ADD][] = $fieldItem->entity;
      }

      return $differences;
    }

    $originalField = $original->get($fieldName);

    foreach ($entityField as $fieldItem) {
      if (!$this->entityHasValue($original, $fieldName, $fieldItem->target_id)) {
        $differences[CorrespondingReferenceOperations::ADD][] = $fieldItem->entity;
      }
    }

    foreach ($originalField as $fieldItem) {
      if (!$this->entityHasValue($entity, $fieldName, $fieldItem->target_id)) {
        $differences[CorrespondingReferenceOperations::REMOVE][] = $fieldItem->entity;
      }
    }

    return $differences;
  }

  protected function entityHasValue(FieldableEntityInterface $entity, $fieldName, $id) {
    if (!$entity->hasField($fieldName)) {
      return FALSE;
    }

    foreach ($entity->get($fieldName) as $fieldItem) {
      if ($fieldItem->target_id == $id) {
        return TRUE;
      }
    }

    return FALSE;
  }
}
