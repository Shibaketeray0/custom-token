<?php

namespace Drupal\eca_form\Plugin\Action;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\eca\Plugin\DataType\DataTransferObject;
use Drupal\eca\Plugin\FormFieldPluginTrait;

/**
 * Base class for form field actions.
 */
abstract class FormFieldActionBase extends FormActionBase {

  use FormFieldPluginTrait;

  /**
   * {@inheritdoc}
   */
  public function access($object, ?AccountInterface $account = NULL, $return_as_object = FALSE) {
    $result = parent::access($object, $account, TRUE);
    $original_field_name = $this->configuration['field_name'];

    foreach (DataTransferObject::buildArrayFromUserInput((string) $this->tokenServices->replace($original_field_name)) as $field_name) {
      $this->configuration['field_name'] = $field_name;
      $result = $result->andIf(AccessResult::allowedIf(!is_null($this->getTargetElement())));
    }

    // Restoring the original config entry.
    $this->configuration['field_name'] = $original_field_name;
    return $return_as_object ? $result : $result->isAllowed();
  }

  /**
   * {@inheritdoc}
   *
   * Optionally allows execution on multiple field names, calling ::doExecute()
   * for each single field name.
   */
  public function execute(): void {
    $original_field_name = $this->configuration['field_name'];

    foreach (DataTransferObject::buildArrayFromUserInput((string) $this->tokenServices->replace($original_field_name)) as $field_name) {
      $this->configuration['field_name'] = $field_name;
      $this->doExecute();
    }

    // Restoring the original config entry.
    $this->configuration['field_name'] = $original_field_name;
  }

  /**
   * Actually performs action execution.
   *
   * This is only relevant when not overriding ::execute() and instead making
   * use of the implementation resided in
   * \Drupal\eca_form\Plugin\Action\FormFieldActionBase::execute().
   */
  protected function doExecute(): void {}

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration(): array {
    return $this->defaultFormFieldConfiguration() + parent::defaultConfiguration();
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state): array {
    $form = parent::buildConfigurationForm($form, $form_state);
    return $this->buildFormFieldConfigurationForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function validateConfigurationForm(array &$form, FormStateInterface $form_state): void {
    $this->validateFormFieldConfigurationForm($form, $form_state);
    parent::validateConfigurationForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state): void {
    $this->submitFormFieldConfigurationForm($form, $form_state);
    parent::submitConfigurationForm($form, $form_state);
  }

}
