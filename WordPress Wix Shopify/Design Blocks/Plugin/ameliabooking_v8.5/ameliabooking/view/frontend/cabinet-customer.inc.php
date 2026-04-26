<?php
/**
 * @copyright © TMS-Plugins. All rights reserved.
 * @licence   See LICENCE.md for license details.
 */

?>

<script>
<?php
    $timeZones = json_encode(\DateTimeZone::listIdentifiers(\DateTimeZone::ALL));
    echo "var wpAmeliaTimeZones = $timeZones;";
?>
  var bookingEntitiesIds = (typeof bookingEntitiesIds === 'undefined') ? [] : bookingEntitiesIds;
  bookingEntitiesIds.push(
    {
      'hasApiCall': 1,
      'trigger': '<?php echo esc_js($atts['trigger']); ?>',
      'counter': '<?php echo esc_js($atts['counter']); ?>',
      'cabinetType': 'customer',
      'appointments': '<?php echo esc_js($atts['appointments']); ?>',
      'events': '<?php echo esc_js($atts['events']); ?>',
    }
  );
</script>

<?php if (empty($atts['version']) || esc_js($atts['version']) === '1') { ?>
    <script>
      var lazyBookingEntitiesIds = (typeof lazyBookingEntitiesIds === 'undefined') ? [] : lazyBookingEntitiesIds;
      if (bookingEntitiesIds[bookingEntitiesIds.length - 1].trigger !== '') {
        lazyBookingEntitiesIds.push(bookingEntitiesIds.pop());
      }
    </script>

    <div
        id="amelia-app-booking<?php echo esc_attr($atts['counter']); ?>"
        class="amelia-cabinet amelia-frontend amelia-app-booking<?php echo $atts['trigger'] !== '' ? ' amelia-skip-load amelia-skip-load-' . esc_attr($atts['counter']) : ''; ?>"
    >
        <cabinet :cabinet-type="'customer'"></cabinet>
    </div>
<?php } else { ?>
    <script>
      var ameliaShortcodeData = (typeof ameliaShortcodeData === 'undefined') ? [] : ameliaShortcodeData;
      ameliaShortcodeData = [...ameliaShortcodeData, ...bookingEntitiesIds];
      var ameliaShortcodeDataTriggered = (typeof ameliaShortcodeDataTriggered === 'undefined') ? [] : ameliaShortcodeDataTriggered;
      if (ameliaShortcodeData[ameliaShortcodeData.length - 1].trigger !== '') {
        if (ameliaShortcodeDataTriggered.filter(a => a.counter === ameliaShortcodeData[ameliaShortcodeData.length - 1].counter).length === 0) {
          ameliaShortcodeDataTriggered.push(ameliaShortcodeData.pop());
        } else {
          ameliaShortcodeData.pop()
        }
      }
    </script>

    <div
        id="amelia-v2-booking-<?php echo esc_attr($atts['counter']); ?>"
        class="amelia-v2-booking <?php echo $atts['trigger'] !== '' ? ' amelia-skip-load amelia-skip-load-' . $atts['counter'] : ''; ?>"
    >
        <customer-panel-wrapper></customer-panel-wrapper>
    </div>
<?php } ?>
