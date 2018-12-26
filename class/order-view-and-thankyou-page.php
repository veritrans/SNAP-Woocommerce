<?php
global $woocommerce;

//create the order object
$order = new WC_Order( $order_id );

// ## Print HTML
?>
<?php if( $order->meta_exists('_mt_payment_url') ) : ?>
  <h2>Payment Info</h2>
  <table class="woocommerce-table shop_table payment_info">
      <tbody>
          <tr>
              <th>Payment Complete?</th>
              <td><?php echo $order->is_paid()? 'Yes. Payment Completed' : 'No' ?></td>
          </tr>
          <?php if( $order->meta_exists('_mt_payment_pdf_url') ) : ?>
          <tr>
              <th>Payment Instructions</th>
              <td><?php echo '<a href="'.$order->get_meta('_mt_payment_pdf_url').'">'.$order->get_meta('_mt_payment_pdf_url').'</a>'?></td>
          </tr>
          <?php else : ?>
          <tr>
              <th>Payment Page</th>
              <td><?php echo '<a href="'.$order->get_meta('_mt_payment_url').'">'.$order->get_meta('_mt_payment_url').'</a>'?></td>
          </tr>
          <?php endif; ?>
      </tbody>
  </table>
<?php endif; ?>
<?php
// ## End of print HTML