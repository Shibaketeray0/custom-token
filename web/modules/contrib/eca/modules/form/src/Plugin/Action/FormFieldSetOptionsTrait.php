<?php

namespace Drupal\eca_form\Plugin\Action;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\Plugin\DataType\EntityAdapter;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\TypedData\TypedDataInterface;
use Drupal\eca\Plugin\Action\ActionBase;
use Drupal\eca\Plugin\DataType\DataTransferObject;
use Drupal\eca\Service\YamlParser;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Yaml\Exception\ParseException;

/**
 * Trait for actions setting available options on a form field.
 */
trait FormFieldSetOptionsTrait {

  /**
   * The YAML parser.
   *
   * @var \Drupal\eca\Service\YamlParser
   */
  protected YamlParser $yamlParser;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition): ActionBase {
    /** @var \Drupal\eca_form\Plugin\Action\FormFieldSetOptions $instance */
    $instance = parent::create($container, $configuration, $plugin_id, $plugin_definition);
    $instance->setYamlParser($container->get('eca.service.yaml_parser'));
    return $instance;
  }

  /**
   * {@inheritdoc}
   */
  public function execute(): void {
    if (!($element = &$this->getTargetElement())) {
      return;
    }
    $element = &$this->jumpToFirstFieldChild($element);
    if (!isset($element['#options'])) {
      return;
    }

    $options = $this->configuration['options'];

    if ($this->configuration['use_yaml']) {
      try {
        $options = $this->yamlParser->parse($options);
      }
      catch (ParseException $e) {
        \Drupal::logger('eca')->error('Tried parsing a options in action "eca_form_field_set_options" as YAML format, but parsing failed.');
        return;
      }
    }
    else {
      $options = $this->buildOptionsArray($options);
    }

    $element['#options'] = $options;
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration(): array {
    return [
      'options' => '',
      'use_yaml' => FALSE,
    ] + parent::defaultConfiguration();
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state): array {
    $form = parent::buildConfigurationForm($form, $form_state);
    $form['options'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Options'),
      '#description' => $this->t('Can be a comma-separated sequence of key-value pairs (e.g. <em>k1:v1,k2:v2</em> or a token that holds a list of key-value pairs. Alternatively use YAML syntax to define one key-value pair per line. Example: <em>key1: "value1"</em>. When using tokens and YAML altogether, make sure that tokens are wrapped as a string. Example: <em>title: "[node:title]"</em>'),
      '#default_value' => $this->configuration['options'],
      '#weight' => -49,
    ];
    $form['use_yaml'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Interpret above value as YAML format'),
      '#description' => $this->t('When using YAML format to define the options above, this option needs to be enabled.'),
      '#default_value' => $this->configuration['use_yaml'],
      '#weight' => -48,
    ];
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state): void {
    $this->configuration['options'] = $form_state->getValue('options');
    $this->configuration['use_yaml'] = !empty($form_state->getValue('use_yaml'));
    parent::submitConfigurationForm($form, $form_state);
  }

  /**
   * Set the YAML parser.
   *
   * @param \Drupal\eca\Service\YamlParser $yaml_parser
   *   The YAML parser.
   */
  public function setYamlParser(YamlParser $yaml_parser): void {
    $this->yamlParser = $yaml_parser;
  }

  /**
   * Builds up an array of options, directly usable in a form element.
   *
   * @param string $input
   *  The unprocessed configuration input, which may hold a token or a fixed
   *  value, or any other sort of values.
   *
   * @return array
   *   The options array.
   */
  protected function buildOptionsArray(string $input): array {
    $token = $this->tokenServices;
    $options = (mb_substr($input, 0, 1) === '[') && (mb_substr($input, -1, 1) === ']') && (mb_strlen($input) <= 255) && $token->hasTokenData($input) ? $token->getTokenData($input) : (string) $token->replaceClear($input);
    $options_array = [];
    if (is_string($options)) {
      $options_array = DataTransferObject::buildArrayFromUserInput($options);
    }
    elseif (is_iterable($options)) {
      foreach ($options as $key => $value) {
        if ($value instanceof EntityAdapter) {
          $value = $value->getValue();
        }
        if ($value instanceof EntityInterface) {
          if (!$value->isNew()) {
            $key = $value->id();
          }
          elseif ($value->uuid()) {
            $key = $value->uuid();
          }
          $value = (string) $value->label();
        }
        elseif ($value instanceof TypedDataInterface) {
          $value = $value->getString();
        }
        elseif (is_object($value) && method_exists($value, '__toString')) {
          $value = (string) $value;
        }
        if (is_scalar($value) && trim((string) $value) !== '') {
          $options_array[$key] = trim((string) $value);
        }
      }
    }
    return $options_array;
  }

}
