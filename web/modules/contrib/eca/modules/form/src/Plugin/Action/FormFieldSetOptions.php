<?php

namespace Drupal\eca_form\Plugin\Action;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Session\AccountInterface;

/**
 * Set available options on a field.
 *
 * @Action(
 *   id = "eca_form_field_set_options",
 *   label = @Translation("Form field: set options"),
 *   description = @Translation("Defines available options on an existing multi-value selection, radio or checkbox field."),
 *   type = "form"
 * )
 */
class FormFieldSetOptions extends FormFieldActionBase {

  use FormFieldSetOptionsTrait;

  /**
   * Whether to use form field value filters or not.
   *
   * @var bool
   */
  protected bool $useFilters = FALSE;

  /**
   * {@inheritdoc}
   */
  public function access($object, ?AccountInterface $account = NULL, $return_as_object = FALSE) {
    $result = parent::access($object, $account, TRUE);
    if ($element = &$this->getTargetElement()) {
      $element = &$this->jumpToFirstFieldChild($element);
    }
    $result = $result->andIf(AccessResult::allowedIf(isset($element['#options'])));
    return $return_as_object ? $result : $result->isAllowed();
  }

}
