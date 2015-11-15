<?php

/**
 * @file
 * Contains \Drupal\og\Plugin\EntityReferenceSelection\OgSelection.
 */

namespace Drupal\og\Plugin\EntityReferenceSelection;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\Plugin\EntityReferenceSelection\DefaultSelection;
use Drupal\Core\Session\AccountInterface;
use Drupal\og\Og;

/**
 * Provide default OG selection handler.
 *
 * @EntityReferenceSelection(
 *   id = "og:default",
 *   label = @Translation("OG selection"),
 *   group = "default",
 *   weight = 1
 * )
 */
class OgSelection extends DefaultSelection {

  /**
   * Get the current account.
   *
   * @return AccountInterface
   */
  public function getAccount() {
    if (empty($this->currentUser)) {
      $this->setAccount(\Drupal::currentUser()->getAccount());
    }

    return $this->currentUser;
  }

  /**
   * Set the current object account.
   *
   * @param AccountInterface $account
   *   The user object.
   * @return $this
   */
  public function setAccount(AccountInterface $account = NULL) {
    $this->currentUser = $account;
    return $this;
  }

  /**
   * Get the field configuration.
   *
   * @param $key
   *   Specific key from the configuration. Optional.
   *
   * @return array|string
   *   Value of a configuration or all the configurations.
   */
  public function getConfiguration($key = NULL) {

    if (!$key) {
      return $this->configuration;
    }

    return isset($this->configuration[$key]) ? $this->configuration[$key] : NULL;
  }

  /**
   * Set a configuration value.
   *
   * @param $key
   *   The key of the configuration.
   * @param $value
   *   The value of the configuration.
   *
   * @return $this
   */
  public function setConfiguration($key, $value) {
    $this->configuration[$key] = $value;
    return $this;
  }

  /**
   * Get the selection handler of the field.
   *
   * @return DefaultSelection
   */
  public function getSelectionHandler() {
    return \Drupal::service('plugin.manager.entity_reference_selection')->getSelectionHandler($this->configuration['field']);
  }

  /**
   * Overrides the basic entity query object. Return only group in the matching
   * results.
   *
   * @param string|null $match
   *   (Optional) Text to match the label against. Defaults to NULL.
   * @param string $match_operator
   *   (Optional) The operation the matching should be done with. Defaults
   *   to "CONTAINS".
   *
   * @return \Drupal\Core\Entity\Query\QueryInterface
   *   The EntityQuery object with the basic conditions and sorting applied to
   *   it.
   */
  protected function buildEntityQuery($match = NULL, $match_operator = 'CONTAINS') {

    // Getting the original entity selection handler. OG selection handler using
    // the default selection handler of the entity, which the field reference
    // to, and add another logic to the query object i.e. check if the entities
    // bundle defined as group.
    $query = $this->getSelectionHandler()->buildEntityQuery();

    $target_type = $this->configuration['target_type'];

    $identifier_key = \Drupal::entityTypeManager()->getDefinition($target_type)->getKey('id');
    $user_groups = $this->getUserGroups();
    $bundles = Og::groupManager()->getAllGroupBundles($target_type);

    $query->condition('type', $bundles, 'IN');

    if (!$user_groups) {
      return $query;
    }

    $ids = [];
    if (isset($this->configuration['handler_settings']['field_mode']) && $this->configuration['handler_settings']['field_mode'] == 'admin') {
      // Don't include the groups, the user doesn't have create permission.
      foreach ($user_groups as $delta => $group) {
        $ids[] = $group->id();
      }

      if ($ids) {
        $query->condition($identifier_key, $ids, 'NOT IN');
      }
    }
    else {
      // Determine which groups should be selectable.
      foreach ($user_groups as $group) {
        $ids[] = $group->id();
      }

      if ($ids) {
        $query->condition($identifier_key, $ids, 'IN');
      }
      else {
        // User doesn't have permission to select any group so falsify this
        // query.
        $query->condition($identifier_key, -1, '=');
      }
    }

    return $query;
  }

  /**
   * Get the user's groups.
   *
   * @return ContentEntityInterface[]
   */
  private function getUserGroups() {
    $other_groups = Og::getEntityGroups('user', $this->getAccount()->id());
    return $other_groups[$this->configuration['target_type']];
  }

}
