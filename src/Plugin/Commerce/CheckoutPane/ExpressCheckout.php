<?php

namespace Drupal\commerce_paypal\Plugin\Commerce\CheckoutPane;

use Drupal\commerce_checkout\Plugin\Commerce\CheckoutPane\CheckoutPaneBase;
use Drupal\commerce_checkout\Plugin\Commerce\CheckoutPane\CheckoutPaneInterface;
use Drupal\Core\Form\FormStateInterface;


/**
 * @CommerceCheckoutPane(
 *  id = "commerce_checkout_pane",
 *  label = @Translation("The plugin ID."),
 *  display_label = @Translation("The plugin ID."),
 *  default_step = "string",
 *  wrapper_element = "string",
 * )
 */
class ExpressCheckout extends CheckoutPaneBase implements CheckoutPaneInterface {


  /**
  * {@inheritdoc}
  */
  public function build() {
  $build = [];

  // Implement your logic

  return $build;
  }


  /**
  * {@inheritdoc}
  */
  public function isVisible() {
    // Determines whether the pane is visible.
    return TRUE;
  }

  /**
  * {@inheritdoc}
  */
  public function buildPaneForm(array $pane_form, FormStateInterface $form_state, array &$complete_form) {
    $pane_form['#theme'] = 'commerce_paypal_express_checkout';

    return $pane_form;
  }

}
