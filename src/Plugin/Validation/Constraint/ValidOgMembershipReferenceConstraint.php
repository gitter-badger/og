<?php

/**
 * @file
 * Contains \Drupal\og\Plugin\Validation\Constraint\ValidOgMembershipReferenceConstraint.
 */

namespace Drupal\og\Plugin\Validation\Constraint;

use Symfony\Component\Validator\Constraint;

/**
 * Entity Reference valid reference constraint.
 *
 * Verifies that referenced entities are valid.
 *
 * @Constraint(
 *   id = "ValidOgMembershipReference",
 *   label = @Translation("Entity Reference valid reference", context = "Validation")
 * )
 */
class ValidOgMembershipReferenceConstraint extends Constraint {

  /**
   * Not a valid group message.
   *
   * @var string
   */
  public $NotValidGroup = 'The entity %label is not defined as a group.';

}
