<?php

namespace Drupal\eca_render\Plugin\Action;

use Drupal\Core\Form\FormStateInterface;

/**
 * Un-serializes / deserializes data.
 *
 * @Action(
 *   id = "eca_render_unserialize",
 *   label = @Translation("Render: unserialize"),
 *   description = @Translation("Un-serializes / deserializes data."),
 *   deriver = "Drupal\eca_render\Plugin\Action\SerializeDeriver"
 * )
 */
class Unserialize extends Serialize {

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration(): array {
    $values = ['type' => 'array'] + parent::defaultConfiguration();
    unset($values['use_yaml']);
    return $values;
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state): array {
    $form = parent::buildConfigurationForm($form, $form_state);
    $data_type_options = [
      'array' => $this->t('Array'),
    ];
    foreach ($this->entityTypeManager->getDefinitions() as $entity_type) {
      $data_type_options[$entity_type->id()] = $entity_type->getLabel();
    }
    $form['type'] = [
      '#type' => 'select',
      '#title' => $this->t('Data type'),
      '#options' => $data_type_options,
      '#default_value' => $this->configuration['type'],
      '#required' => TRUE,
      '#weight' => -200,
    ];
    $form['value']['#description'] = $this->t('The value to deserialize. This field supports tokens.');
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state): void {
    parent::submitConfigurationForm($form, $form_state);
    $this->configuration['type'] = $form_state->getValue('type');
  }

  /**
   * {@inheritdoc}
   */
  protected function doBuild(array &$build): void {
    $value = $this->configuration['value'];
    $serialized = (string) $this->tokenServices->replace($value);

    $format = $this->configuration['format'];
    $type = $this->configuration['type'];
    if (($type !== 'array') && $this->entityTypeManager->hasDefinition($type)) {
      $type = $this->entityTypeManager->getDefinition($type)->getClass();
    }
    $data = $this->serializer->deserialize($serialized, $type, $format);
    $build = [
      '#theme' => 'eca_serialized',
      '#method' => 'unserialize',
      '#serialized' => $serialized,
      '#format' => $format,
      '#data' => $data,
    ];
  }

}
