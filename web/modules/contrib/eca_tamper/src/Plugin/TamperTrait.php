<?php

namespace Drupal\eca_tamper\Plugin;

use Drupal\Component\Plugin\Exception\PluginException;
use Drupal\Core\Form\FormStateInterface;
use Drupal\tamper\SourceDefinition;
use Drupal\tamper\TamperInterface;
use Drupal\tamper\TamperManagerInterface;

/**
 * Trait for ECA tamper actions and conditions.
 */
trait TamperTrait {

  /**
   * The tamper plugin manager.
   *
   * @var \Drupal\tamper\TamperManagerInterface
   */
  protected TamperManagerInterface $tamperManager;

  /**
   * The tamper plugin.
   *
   * @var \Drupal\tamper\TamperInterface
   */
  protected TamperInterface $tamperPlugin;

  /**
   * Return the tamper plugin after it has been fully configured.
   *
   * @return \Drupal\tamper\TamperInterface
   *   This tamper action plugin.
   *
   * @throws \Drupal\Component\Plugin\Exception\PluginException
   */
  protected function tamperPlugin(): TamperInterface {
    if (!isset($this->tamperPlugin)) {
      /* @noinspection PhpFieldAssignmentTypeMismatchInspection */
      $this->tamperPlugin = $this->tamperManager->createInstance($this->pluginDefinition['tamper_plugin'], ['source_definition' => new SourceDefinition([])]);

      $configuration = $this->configuration;
      unset($configuration['eca_data'], $configuration['eca_token_name']);
      $this->tamperPlugin->setConfiguration($configuration);
    }
    return $this->tamperPlugin;
  }

  /**
   * {@inheritdoc}
   */
  public function tamperDefaultConfiguration(): array {
    if (!isset($this->tamperManager)) {
      return parent::defaultConfiguration();
    }
    try {
      $pluginDefault = $this->tamperPlugin()->defaultConfiguration();
    }
    catch (PluginException $e) {
      $pluginDefault = [];
    }
    return $pluginDefault +
      parent::defaultConfiguration();
  }

  /**
   * {@inheritdoc}
   */
  public function buildTamperConfigurationForm(array $form, FormStateInterface $form_state): array {
    $form = parent::buildConfigurationForm($form, $form_state);
    try {
      return $this->tamperPlugin()->buildConfigurationForm($form, $form_state);
    }
    catch (PluginException $e) {
      // @todo Do we need to log this?
    }
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateTamperConfigurationForm(array &$form, FormStateInterface $form_state): void {
    parent::validateConfigurationForm($form, $form_state);
    try {
      $this->tamperPlugin()->validateConfigurationForm($form, $form_state);
    }
    catch (PluginException $e) {
      // @todo Do we need to log this?
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitTamperConfigurationForm(array &$form, FormStateInterface $form_state): void {
    parent::submitConfigurationForm($form, $form_state);
    try {
      $this->tamperPlugin()->submitConfigurationForm($form, $form_state);
    }
    catch (PluginException $e) {
      // @todo Do we need to log this?
    }
  }

}
