jQuery(document).ready(function($) {
  // Check when a product is added to the cart
  $('body').on('added_to_cart', function(){
      var notice = $('.woocommerce-error').text();
      
      if(notice.includes('You cannot add products from multiple categories')){
          alert('You cannot add products from multiple categories. Please remove the previous one.');
      }
  });
});
