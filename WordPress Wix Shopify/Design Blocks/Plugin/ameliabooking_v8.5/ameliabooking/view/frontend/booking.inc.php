<?php
/**
 * @copyright © TMS-Plugins. All rights reserved.
 * @licence   See LICENCE.md for license details.
 */

?>

<script>
  if (typeof hasAmeliaAppointment === 'undefined') {
    var hasAmeliaAppointment = true;
  }
  if (typeof hasAppointmentApiCall === 'undefined' && '<?php echo esc_js($atts['trigger']); ?>' === '') {
    var hasAppointmentApiCall = true;
  }
  var hasBookingShortcode = (typeof hasBookingShortcode === 'undefined') ? false : true;
  var bookingEntitiesIds = (typeof bookingEntitiesIds === 'undefined') ? [] : bookingEntitiesIds;
  bookingEntitiesIds.push(
    {
      'hasApiCall': (typeof hasAppointmentApiCall !== 'undefined') && hasAppointmentApiCall,
      'trigger': '<?php echo esc_js($atts['trigger']); ?>',
      'show': '<?php echo esc_js($atts['show']); ?>',
      'counter': '<?php echo esc_js($atts['counter']); ?>',
      'category': '<?php echo esc_js($atts['category']); ?>',
      'service': '<?php echo esc_js($atts['service']); ?>',
      'employee': '<?php echo esc_js($atts['employee']); ?>',
      'location': '<?php echo esc_js($atts['location']); ?>'
    }
  );
  var lazyBookingEntitiesIds = (typeof lazyBookingEntitiesIds === 'undefined') ? [] : lazyBookingEntitiesIds;
  if (bookingEntitiesIds[bookingEntitiesIds.length - 1].trigger !== '') {
    lazyBookingEntitiesIds.push(bookingEntitiesIds.pop());
  }
  if (typeof hasAppointmentApiCall !== 'undefined' && hasAppointmentApiCall) {
    hasAppointmentApiCall = false;
  }
</script>

<div id="amelia-app-booking<?php echo esc_attr($atts['counter']); ?>" class="amelia-booking amelia-frontend amelia-app-booking<?php echo $atts['trigger'] !== '' ? ' amelia-skip-load amelia-skip-load-' . esc_attr($atts['counter']) : ''; ?>">
  <booking id="amelia-step-booking<?php echo esc_attr($atts['counter']); ?>"></booking>
</div>
