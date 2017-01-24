<?php

namespace Drupal\metatag_path\EventSubscriber;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class MetatagPathSubscriber implements EventSubscriberInterface {

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    $events = [];

    // TODO: Register event to update corresponding references when entity types are updated

    return $events;
  }
}
