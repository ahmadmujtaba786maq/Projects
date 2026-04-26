<?php

use AmeliaBooking\Infrastructure\WP\Translations\BackendStrings;

class DIVI_Employee extends ET_Builder_Module
{

    public $slug       = 'divi_employee';
    public $vb_support = 'on';


    protected $module_credits = array(
        'module_uri' => '',
        'author'     => '',
        'author_uri' => '',
    );

    public function init()
    {
        $this->name = esc_html__(BackendStrings::getWordPressStrings()['employee_cabinet_divi'], 'divi-divi_amelia');
    }

    /**
     * Advanced Fields Config
     *
     * @return array
     */
    public function get_advanced_fields_config()
    {
        return array(
            'button' => false,
            'link_options' => false
        );
    }

    public function get_fields()
    {
        return array(
            'appointments' => array(
                'label'           => esc_html__(BackendStrings::getCommonStrings()['appointments'], 'divi-divi_amelia'),
                'type'            => 'yes_no_button',
                'options' => array(
                    'on'  => esc_html__(BackendStrings::getCommonStrings()['yes'], 'divi-divi_amelia'),
                    'off' => esc_html__(BackendStrings::getCommonStrings()['no'], 'divi-divi_amelia'),
                ),
                'default'         => 'on',
                'toggle_slug'     => 'main_content',
                'option_category' => 'basic_option',
            ),
            'events' => array(
                'label'           => esc_html__(BackendStrings::getCommonStrings()['events'], 'divi-divi_amelia'),
                'type'            => 'yes_no_button',
                'options' => array(
                    'on'  => esc_html__(BackendStrings::getCommonStrings()['yes'], 'divi-divi_amelia'),
                    'off' => esc_html__(BackendStrings::getCommonStrings()['no'], 'divi-divi_amelia'),
                ),
                'default'         => 'on',
                'toggle_slug'     => 'main_content',
                'option_category' => 'basic_option',
            ),
            'profile' => array(
                'label'           => esc_html__(BackendStrings::getCommonStrings()['profile'], 'divi-divi_amelia'),
                'type'            => 'yes_no_button',
                'options' => array(
                    'on'  => esc_html__(BackendStrings::getCommonStrings()['yes'], 'divi-divi_amelia'),
                    'off' => esc_html__(BackendStrings::getCommonStrings()['no'], 'divi-divi_amelia'),
                ),
                'default'         => 'off',
                'toggle_slug'     => 'main_content',
                'option_category' => 'basic_option',
            ),
            'trigger' => array(
                'label'           => esc_html__(BackendStrings::getWordPressStrings()['manually_loading'], 'divi-divi_amelia'),
                'type'            => 'text',
                'toggle_slug'     => 'main_content',
                'option_category' => 'basic_option',
                'description'     => BackendStrings::getWordPressStrings()['manually_loading_description'],
            ),
            'version' => array(
                'label'           => esc_html__(BackendStrings::getWordPressStrings()['choose_panel_version'], 'divi-divi_amelia'),
                'type'            => 'select',
                'options' => array(
                    1  => esc_html__(BackendStrings::getWordPressStrings()['panel_version_old'], 'divi-divi_amelia'),
                    2  => esc_html__(BackendStrings::getWordPressStrings()['panel_version_new'], 'divi-divi_amelia'),
                ),
                'toggle_slug'     => 'main_content',
                'option_category' => 'basic_option',
            )
        );
    }

    public function render($attrs, $content = null, $render_slug = null)
    {
        $shortcode    = '[ameliaemployeepanel';
        $trigger      = $this->props['trigger'];
        $appointments = $this->props['appointments'];
        $events       = $this->props['events'];
        $profile      = $this->props['profile'];
        $version      = $this->props['version'];
        if ($trigger !== null && $trigger !== '') {
            $shortcode .= ' trigger='.$trigger;
        }
        if ($version !== null && $version !== '') {
            $shortcode .= ' version='.$version;
        }
        if ($appointments === 'on') {
            $shortcode .= ' appointments=1';
        }
        if ($events === 'on') {
            $shortcode .= ' events=1';
        }
        if ($profile === 'on') {
            $shortcode .= ' profile-hidden=1';
        }
        $shortcode .= ']';

        return do_shortcode($shortcode);
    }
}

new DIVI_Employee;
