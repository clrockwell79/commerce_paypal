<?php

namespace Drupal\commerce_paypal\Controller;

use Drupal\commerce_cart\CartProviderInterface;
use Drupal\commerce_order\Entity\Order;
use Drupal\commerce_payment\Entity\Payment;
use Drupal\commerce_payment\Exception\PaymentGatewayException;
use Drupal\commerce_payment\PaymentGatewayManager;
use Drupal\commerce_paypal\Plugin\Commerce\PaymentGateway\ExpressCheckout;
use Drupal\Component\Serialization\Json;
use Drupal\Core\Config\ConfigFactory;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Url;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Response;

/**
 * Class ExpressCheckoutPayment.
 */
class ExpressCheckoutPayment extends ControllerBase {

  /**
   * @var \Drupal\commerce_payment\PaymentGatewayManager
   */
  protected $paymentGateway;

  /**
   * @var \Drupal\Core\Config\ConfigFactory
   */
  protected $configFactory;

  /**
   * The cart provider.
   *
   * @var \Drupal\commerce_cart\CartProviderInterface
   */
  protected $cartProvider;

  /**
   * ExpressCheckoutPayment constructor.
   * @param \Drupal\commerce_payment\PaymentGatewayManager $paymentGatewayManager
   */
  public function __construct(PaymentGatewayManager $paymentGatewayManager, ConfigFactory $configFactory, CartProviderInterface $cartProvider) {
    $this->paymentGateway = $paymentGatewayManager;
    $this->configFactory = $configFactory;
    $this->cartProvider = $cartProvider;
  }


  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('plugin.manager.commerce_payment_gateway'),
      $container->get('config.factory'),
      $container->get('commerce_cart.cart_provider')
    );
  }

  /**
   * Createpayment.
   *
   * @return string
   *   Return Hello string.
   */
  public function createPayment() {

    // @todo probably some caching.

    $payment_storage = $this->entityTypeManager()
      ->getStorage('commerce_payment');

    $config = $this->configFactory->get('commerce_payment.commerce_payment_gateway.paypal_ec');
    $_config = $config->get();
    /** @var ExpressCheckout $instance */
    $instance = $this->paymentGateway->createInstance('paypal_express_checkout', $config->get()['configuration']);
    $carts = $this->getCarts();
    if (!$carts) {
      throw new \Exception('No carts found');
    }
    // @todo not sure how to deal with multiple carts at this point
    /** @var Order $cart */
    $cart = array_pop($carts);

    /** @var Payment $payment */
    $payment = $payment_storage->create([
      'state' => 'new',
      'amount' => $cart->getTotalPrice(),
      'payment_gateway' => 'paypal_ec', // @todo hardcoded no good
      'order_id' => $cart->id(),
    ]);

    $extra = [
      'return_url' => $this->buildReturnUrl($cart)->toString(),
      'cancel_url' => $this->buildCancelUrl($cart)->toString(),
      'capture' => TRUE,
    ];

    $paypal_response = $instance->setExpressCheckout($payment, $extra);
    return new Response(Json::encode([
      'paymentID' => $paypal_response['TOKEN']
    ]));
  }

  protected function getCarts() {
    $carts = $this->cartProvider->getCarts();
    $carts = array_filter($carts, function ($cart) {
      /** @var \Drupal\commerce_order\Entity\OrderInterface $cart */
      return $cart->hasItems();
    });

    return $carts;
  }


  // Straight outta PaymentProcess.php

  /**
   * Builds the URL to the "return" page.
   *
   * @param Order $cart
   *
   * @return \Drupal\Core\Url
   *   The "return" page URL.
   */
  protected function buildReturnUrl($cart) {
    return Url::fromRoute('commerce_payment.checkout.return', [
      'commerce_order' => $cart->id(),
      'step' => 'payment',
    ], ['absolute' => TRUE]);
  }

  /**
   * Builds the URL to the "cancel" page.
   *
   * @return \Drupal\Core\Url
   *   The "cancel" page URL.
   */
  protected function buildCancelUrl($cart) {
    return Url::fromRoute('commerce_payment.checkout.cancel', [
      'commerce_order' => $cart->id(),
      'step' => 'payment',
    ], ['absolute' => TRUE]);
  }

}
