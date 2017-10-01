console.log('here');
paypal.Button.render({

  env: 'sandbox', // sandbox | production

  client: {
    sandbox:    'Aa5FCTG0zocLNNLB2GJu2x_chZNdea0z8blrc0uyJ9Scd90ik9c35yW4Z8brGDzoGsugnh8FFF_cPmyT'
  },

  // Show the buyer a 'Pay Now' button in the checkout flow
  commit: true,
  // payment() is called when the button is clicked
  payment: function() {

    // Set up a url on your server to create the payment
    // @todo should probably use the route here: commerce_paypal.express_checkout_payment_createPayment
    var CREATE_URL = '/commerce_paypal/create-payment';

    // Make a call to your server to set up the payment
    return paypal.request.post(CREATE_URL)
      .then(function(res) {
        return res.paymentID;
      });
  },

  // onAuthorize() is called when the buyer approves the payment
  onAuthorize: function(data, actions) {

    // Set up a url on your server to execute the payment
    var EXECUTE_URL = '/commerce_paypal/express-checkout/create';

    // Set up the data you need to pass to your server
    var data = {
      paymentID: data.paymentID,
      payerID: data.payerID
    };

    // Make a call to your server to execute the payment
    return paypal.request.post(EXECUTE_URL, data)
      .then(function (res) {
        window.alert('Payment Complete!');
      });
  }

}, '#paypal-button-container');