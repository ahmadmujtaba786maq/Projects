<?php
?>
<div class="notice notice-info is-dismissible am-promote-wpdt-notice">
    <div id="am-promote-wpdt-section">
        <div id="am-first-section">
            <img class="am-wpdt-logo" src="<?php echo AMELIA_URL ?>public/wpdt/img/wpdt-promo-left.webp" alt="WPDT Left">
        </div>
        <div id="am-second-section">
            <img class="am-wpdt-logo" src="<?php echo AMELIA_URL ?>public/wpdt/img/wpDataTables-logo.svg" alt="WPDT Logo">
            <img class="am-wpdt-logo-mobile" src="<?php echo AMELIA_URL ?>public/wpdt/img/wpDT-logo-w.webp" alt="WPDT Logo">
            <p class="am-wpdt-message"><?php esc_html_e("Have you tried our table plugin?", "wpamelia") ?></p>
            <button id="am-download-it" onclick="window.open('https://wpdatatables.com/pricing/?utm_source=amelia-lite&utm_medium=banner&utm_content=wpdt&utm_campaign=wpdt', '_blank')">
                <?php esc_html_e("Get started", "wpamelia") ?>
            </button>
        </div>
        <div id="am-third-section">
            <img class="am-wpdt-pic-1" src="<?php echo AMELIA_URL ?>public/wpdt/img/wpdt-promo-right.webp" alt="WPDT right">
        </div>
    </div>
    <button type="button" class="notice-dismiss"><span class="screen-reader-text">Dismiss this notice.</span></button>
</div>
<script>
    (function ($) {
        $(function () {
            $('.am-promote-wpdt-notice .notice-dismiss').on('click', function (e) {
                e.preventDefault()
                $.ajax({
                    url: ajaxurl,
                    method: 'POST',
                    data: {
                        'action': 'amelia_remove_wpdt_promo_notice'
                    },
                    dataType: 'json',
                    async: !0,
                    success: function (e) {
                        if (e == 'success') {
                            $('.am-promote-wpdt-notice').slideUp('fast')
                        }
                    }
                })
            })
        })
    })(jQuery)

</script>

