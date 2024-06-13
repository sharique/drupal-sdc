<?php

declare(strict_types=1);

namespace Drupal\saf_msf\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Render\Element\Form;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Session\SessionManagerInterface;
use Drupal\Core\TempStore\PrivateTempStoreFactory;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a SAF multi step form ajax example form.
 */
final class MultiStepAjaxForm extends FormBase {

  /**
   * @var \Drupal\user\PrivateTempStoreFactory
   */
  protected $tempStoreFactory;

  /**
   * @var \Drupal\Core\Session\SessionManagerInterface
   */
  private $sessionManager;

  /**
   * @var \Drupal\Core\Session\AccountInterface
   */
  private $currentUser;

  /**
   * @var \Drupal\Core\TempStore\PrivateTempStoreFactory
   */
  protected $store;

  /**
   * Constructs a \Drupal\demo\Form\Multistep\MultistepFormBase.
   *
   * @param \Drupal\Core\TempStore\PrivateTempStoreFactory $temp_store_factory
   * @param \Drupal\Core\Session\SessionManagerInterface $session_manager
   * @param \Drupal\Core\Session\AccountInterface $current_user
   */
  public function __construct(
    PrivateTempStoreFactory $temp_store_factory,
    SessionManagerInterface $session_manager,
    AccountInterface $current_user
  ) {
    $this->tempStoreFactory = $temp_store_factory;
    $this->sessionManager = $session_manager;
    $this->currentUser = $current_user;

    $this->store = $this->tempStoreFactory->get('multistep_data');
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('tempstore.private'),
      $container->get('session_manager'),
      $container->get('current_user')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId(): string {
    return 'saf_msf_multi_step';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state): array {
    //    // Start a manual session for anonymous users.
    //    if ($this->currentUser->isAnonymous() && !isset($_SESSION['multistep_form_holds_session'])) {
    //      $_SESSION['multistep_form_holds_session'] = TRUE;
    //      $this->sessionManager->start();
    //    }

    $form['container'] = [
      '#type' => 'container',
      '#attributes' => [
        'class' => 'saf-container',
        'id' => 'ajx-container',
      ],
    ];

    print_r($form_state->getTemporaryValue('step'));
    if (!$form_state->getTemporaryValue('step')) {
      $form_state->setTemporaryValue('step', 1);
    }

    switch ($form_state->getTemporaryValue('step')) {
      default:
      case 1:
        $form['container'] = $this->buildDateForm($form, $form_state);
        break;
      case 2:
        $form['container']  = $this->buildTimeForm($form, $form_state);
        break;
      case 3:
        $form['container']  = $this->buildContactForm($form, $form_state);
        break;
      case 4:
        $form['container'] = $this->buildConfirmForm($form, $form_state);
        break;
    }
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state): void {}

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state): void {}

  public function buildDateForm(array $form, FormStateInterface $form_state) {
    $form['container']['slot'] = [
      '#type' => 'date',
      '#required' => TRUE,
      '#title' => $this->t('Please select date'),
    ];
    $form['container']['actions']['next'] = [
      '#type' => 'submit',
      '#button_type' => 'primary',
      '#value' => $this->t('Continue to time selection'),
      '#ajax' => [
        'callback' => '::dateFormSubmit',
        'wrapper' => 'ajx-container',
      ],
      '#states' => [
        'enabled' => [
          ':input[name="slot"]' => ['filled' => TRUE],
        ],
      ],
    ];
    return $form['container'];
  }

  public function dateFormSubmit(array &$form, FormStateInterface $form_state) {
    $form_state->setTemporaryValue('step', 2)->setRebuild(TRUE);
    $this->store->set('slot_date', $form_state->getValue('slot'));
    unset($form['container']);
    $form['container'] = [
      '#type' => 'container',
      '#attributes' => [
        'class' => 'saf-container',
        'id' => 'ajx-container',
      ],
    ];
    $form['container'] = $this->buildTimeForm($form, $form_state);
    return $form['container'];
  }

  public function buildTimeForm(array $form, FormStateInterface $form_state) {
    $form['container']['previous'] = [
      '#type' => 'button',
      '#button_type' => 'secondary',
      '#value' => $this->t('back to date selection'),
      '#submit' => ['::timeBackFormSubmit'],
      '#limit_validation_errors' => [],
    ];
    $form['container']['summary'] = [
      '#type' => 'html_tag',
      '#tag' => 'h3',
      '#value' => $this->store->get('slot_date'),
    ];
    $options = [
      'one',
      '7:00',
      '8:00',
    ];
    $form['container']['time'] = [
      '#type' => 'select',
      '#required' => TRUE,
      '#title' => $this->t('Please select time'),
      '#options' => array_combine($options, $options),
    ];

    $form['container']['actions']['time_next'] = [
      '#type' => 'submit',
      '#button_type' => 'primary',
      '#value' => $this->t('Continue to contact details'),
      '#ajax' => [
        'callback' => '::timeFormSubmit',
        'wrapper' => 'ajx-container',
      ],
      '#states' => [
        'enabled' => [
          ':input[name="slot"]' => ['filled' => TRUE],
        ],
      ],
    ];
    return $form['container'];
  }

  public function timeFormSubmit(array &$form, FormStateInterface $form_state) {
    $form_state->setTemporaryValue('step', 3)->setRebuild(TRUE);
    $this->store->set('slot_time', $form_state->getValue('time'));
    $form['container'] = $this->buildContactForm($form, $form_state);
    return $form['container'];
  }

  public function timeBackFormSubmit(array &$form, FormStateInterface $form_state): void {
    $form_state->setTemporaryValue('step', 1)->setRebuild(TRUE);
  }

  public function buildContactForm(array $form, FormStateInterface $form_state) {
    $form['previous'] = [
      '#type' => 'button',
      '#button_type' => 'secondary',
      '#value' => $this->t('back to time selection'),
      '#submit' => ['::contactBackFormSubmit'],
      '#limit_validation_errors' => [],
    ];
    $form['summary'] = [
      '#type' => 'html_tag',
      '#tag' => 'h3',
      '#value' => $this->store->get('slot_date') . ' and time is ' . $this->store->get('slot_time'),
    ];
    $form['name'] = [
      '#type' => 'textfield',
      '#required' => TRUE,
      '#title' => $this->t('Please enter name'),

    ];
    $form['actions']['next'] = [
      '#type' => 'submit',
      '#button_type' => 'primary',
      '#value' => $this->t('Continue to contact details'),
      '#submit' => ['::contactFormSubmit'],
      '#states' => [
        'enabled' => [
          ':input[name="slot"]' => ['filled' => TRUE],
        ],
      ],
    ];
    return $form;
  }

  public function contactFormSubmit(array &$form, FormStateInterface $form_state): void {
    $form_state->setTemporaryValue('step', 4)->setRebuild(TRUE);
    $this->store->set('name', $form_state->getValue('name'));
  }

  public function contactBackFormSubmit(array &$form, FormStateInterface $form_state): void {
    $form_state->setTemporaryValue('step', 2)->setRebuild(TRUE);
  }

  public function buildConfirmForm(array $form, FormStateInterface $form_state) {
    $form['summary'] = [
      '#type' => 'html_tag',
      '#tag' => 'h3',
      '#value' => $this->store->get('slot_date') . ' and time is ' .
        $this->store->get('slot_time') . ' and name ' . $this->store->get('name'),
    ];

    return $form;
  }

}
