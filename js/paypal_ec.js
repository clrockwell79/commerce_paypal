console.log(Drupal);
paypal.Button.render({

  env: 'sandbox', // sandbox | production

  // Show the buyer a 'Pay Now' button in the checkout flow
  commit: true,
  // payment() is called when the button is clicked
  payment: function() {

    // Set up a url on your server to create the payment
    // @todo should probably use the route here: commerce_paypal.express_checkout_payment_createPayment
    var CREATE_URL = '/commerce_paypal/express-checkout/set-express-checkout';

    // Make a call to your server to set up the payment
    return paypal.request.post(CREATE_URL)
      .then(function(res) {
        return res.paymentID;
      });
  },

  // onAuthorize() is called when the buyer approves the payment
  onAuthorize: function(data, paypal_actions) {

    // Set up a url on your server to execute the payment
    var EXECUTE_URL = '/commerce_paypal/express-checkout/return';


    // Make a call to your server to execute the payment
    paypal.request.post(EXECUTE_URL, data)
      .then(function (res) {});
  }

}, '#paypal-button-container');