<?php

class DIVI_DiviAmelia extends DiviExtension
{
    /**
     * The gettext domain for the extension's translations.
     *
     * @since 1.0.0
     *
     * @var string
     */
    public $gettext_domain = 'divi-divi_amelia';

    /**
     * The extension's WP Plugin name.
     *
     * @since 1.0.0
     *
     * @var string
     */
    public $name = 'divi_amelia';

    /**
     * The extension's version
     *
     * @since 1.0.0
     *
     * @var string
     */
    public $version = '1.0.0';

    /**
     * DIVI_DiviAmelia constructor.
     *
     * @param string $name
     * @param array  $args
     */
    public function __construct($name = 'divi_amelia', $args = array())
    {
        $this->plugin_dir     = plugin_dir_path(__FILE__);
        $this->plugin_dir_url = plugin_dir_url($this->plugin_dir);

        parent::__construct($name, $args);
    }
}

new DIVI_DiviAmelia;
