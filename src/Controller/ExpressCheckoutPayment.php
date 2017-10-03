<?php

namespace Drupal\commerce_paypal\Controller;

use Drupal\commerce_cart\CartProviderInterface;
use Drupal\commerce_order\Entity\Order;
use Drupal\commerce_payment\Entity\Payment;
use Drupal\commerce_payment\PaymentGatewayManager;
use Drupal\Component\Serialization\Json;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Url;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\RequestStack;
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
   * The cart provider.
   *
   * @var \Drupal\commerce_cart\CartProviderInterface
   */
  protected $cartProvider;

  /**
   * The request stack.
   *
   * @var \Symfony\Component\HttpFoundation\RequestStack
   */
  protected $requestStack;

  /**
   * The payment gateway instance
   *
   * @var \Drupal\commerce_paypal\Plugin\Commerce\PaymentGateway\ExpressCheckout
   */
  protected $paymentPlugin;

  /**
   * ExpressCheckoutPayment constructor.
   * @param \Drupal\commerce_payment\PaymentGatewayManager $paymentGatewayManager
   */
  public function __construct(
    PaymentGatewayManager $paymentGatewayManager,
    CartProviderInterface $cartProvider,
    RequestStack $requestStack

  ) {
    $this->paymentGateway = $paymentGatewayManager;
    $this->cartProvider = $cartProvider;
    $this->requestStack = $requestStack;
    $gateway = $this->entityTypeManager()
      ->getStorage('commerce_payment_gateway')->loadByProperties([
        'plugin' => 'paypal_express_checkout',
      ]);
    $gateway = reset($gateway);
    $this->paymentPlugin = $gateway->getPlugin();
  }


  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('plugin.manager.commerce_payment_gateway'),
      $container->get('commerce_cart.cart_provider'),
      $container->get('request_stack')
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

    $carts = $this->getCarts();
    if (!$carts) {
      throw new \Exception('No carts found');
    }
    // @todo not sure how to deal with multiple carts at this point
    // @todo more and more sure this is not correct.
    /** @var Order $cart */
    $cart = array_pop($carts);


    // @todo somthing might be wrong here as we're creating a payment, but creating another in ExpressCheckout::onReturn().
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
      'capture' => TRUE, // @todo hardcoding this can't be right
    ];

    // @todo we have an opportunity to capture some errors here
    $paypal_response = $this->paymentPlugin->setExpressCheckout($payment, $extra);

    $order = $payment->getOrder();
    $order->setData('paypal_express_checkout', [
      'flow' => 'ec',
      'token' => $paypal_response['TOKEN'],
      'payerid' => FALSE,
      'capture' => $extra['capture'],
    ]);
    $order->save();

    return new Response(Json::encode([
      'paymentID' => $paypal_response['TOKEN'],
      'orderID' => $cart->id(),
    ]));
  }

  public function onReturn() {
    $method = $this->requestStack->getCurrentRequest()->getMethod();
    if ($this->requestStack->getCurrentRequest()->getMethod() == 'POST') {
      $request = $this->requestStack->getCurrentRequest();
      $test = $request->get('test');
      $data = $this->requestStack->getParentRequest()->query->all();
      $carts = $this->getCarts();
      // @todo can't be right
      /** @var Order $order */
      $order = array_pop($carts);

      $onReturn = $this->paymentPlugin->onReturn($order, $request);

      // @todo payment
    }

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
