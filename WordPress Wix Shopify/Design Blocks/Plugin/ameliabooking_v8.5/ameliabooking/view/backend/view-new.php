<?php defined('ABSPATH') or die('No script kiddies please!'); ?>
<?php
if (!empty($_GET['page']) && !empty($_GET['code'])) {
    $code = esc_attr($_GET['code']);
    echo "
<script>
var url = new URL(window.location.href);
url.searchParams.set('code', '" . $code . "');
window.history.replaceState(null, null, url);
</script>
    ";
}
?>
<!--suppress JSUnusedLocalSymbols -->
<script>
  var wpAmeliaUploadsAmeliaURL = '<?php echo AMELIA_UPLOADS_FILES_URL; ?>';
  var wpAmeliaUseUploadsAmeliaPath = '<?php echo AMELIA_UPLOADS_FILES_PATH_USE; ?>';
  var wpAmeliaPluginURL = '<?php echo AMELIA_URL; ?>';
  var wpAmeliaPluginAjaxURL = '<?php echo AMELIA_ACTION_URL; ?>';
  var wpAmeliaPluginStoreURL = '<?php echo AMELIA_STORE_API_URL; ?>';
  var wpAmeliaSiteURL = '<?php echo AMELIA_SITE_URL; ?>';
  var wpAmeliaNonce = '<?php echo wp_create_nonce('ajax-nonce'); ?>';
  var menuPage = '<?php echo isset($page) ? (string)$page : ''; ?>';
  var wpAmeliaSMSVendorId = '<?php echo AMELIA_SMS_VENDOR_ID; ?>';
  var wpAmeliaSMSProductId10 = '<?php echo AMELIA_SMS_PRODUCT_ID_10; ?>';
  var wpAmeliaSMSProductId20 = '<?php echo AMELIA_SMS_PRODUCT_ID_20; ?>';
  var wpAmeliaSMSProductId50 = '<?php echo AMELIA_SMS_PRODUCT_ID_50; ?>';
  var wpAmeliaSMSProductId100 = '<?php echo AMELIA_SMS_PRODUCT_ID_100; ?>';
  var wpAmeliaSMSProductId200 = '<?php echo AMELIA_SMS_PRODUCT_ID_200; ?>';
  var wpAmeliaSMSProductId500 = '<?php echo AMELIA_SMS_PRODUCT_ID_500; ?>';

  window.wpAmeliaUrls = {}
  window.wpAmeliaUrls.wpAmeliaPluginURL = location.protocol === 'https:' ? window.wpAmeliaPluginURL.replace('http:', 'https:') : window.wpAmeliaPluginURL;
  window.wpAmeliaUrls.wpAmeliaPluginAjaxURL = location.protocol === 'https:' ? window.wpAmeliaPluginAjaxURL.replace('http:', 'https:') : window.wpAmeliaPluginAjaxURL;
</script>
<div id="amelia-app-backend-new" class="amelia-app-backend-new">
  <Customize></Customize>
</div>
