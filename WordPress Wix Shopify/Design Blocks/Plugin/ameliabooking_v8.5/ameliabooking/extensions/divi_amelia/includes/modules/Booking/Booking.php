<?php

use AmeliaBooking\Infrastructure\WP\GutenbergBlock\GutenbergBlock;
use AmeliaBooking\Infrastructure\WP\Translations\BackendStrings;

class DIVI_Booking extends ET_Builder_Module
{

    public $slug       = 'divi_booking';
    public $vb_support = 'on';

    private $categories   = array();
    private $services     = array();
    private $employees    = array();
    private $locations    = array();
    private $showPackages = true;

    public $type = array();


    protected $module_credits = array(
        'module_uri' => '',
        'author'     => '',
        'author_uri' => '',
    );

    public function init()
    {
        $this->name = esc_html__(BackendStrings::getWordPressStrings()['booking_divi'], 'divi-divi_amelia');

        $this->type['0']        = BackendStrings::getWordPressStrings()['show_all'];
        $this->type['services'] = BackendStrings::getCommonStrings()['services'];
        $this->type['packages'] = BackendStrings::getCommonStrings()['packages'];

        if (!is_admin()) {
            return;
        }

        $data = GutenbergBlock::getEntitiesData()['data'];
        $this->showPackages = !empty($data['packages']);

        $this->categories['0'] = BackendStrings::getWordPressStrings()['show_all_categories'];
        foreach ($data['categories'] as $category) {
            $this->categories[$category['id']] = $category['name']. ' (id: ' . $category['id'] . ')';
        }
        $this->services['0'] = BackendStrings::getWordPressStrings()['show_all_services'];
        foreach ($data['servicesList'] as $service) {
            if ($service) {
                $this->services[$service['id']] = $service['name']. ' (id: ' . $service['id'] . ')';
            }
        }
        $this->employees['0'] = BackendStrings::getWordPressStrings()['show_all_employees'];
        foreach ($data['employees'] as $employee) {
            $this->employees[$employee['id']] = $employee['firstName'] . ' ' . $employee['lastName'] . ' (id: ' . $employee['id'] . ')';
        }
        $this->locations['0'] = BackendStrings::getWordPressStrings()['show_all_locations'];
        foreach ($data['locations'] as $location) {
            $this->locations[$location['id']] = $location['name']. ' (id: ' . $location['id'] . ')';
        }
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
        $array = array(
            'booking_params' => array(
                'label'           => esc_html__(BackendStrings::getWordPressStrings()['filter'], 'divi-divi_amelia'),
                'type'            => 'yes_no_button',
                'options' => array(
                    'on'  => esc_html__(BackendStrings::getCommonStrings()['yes'], 'divi-divi_amelia'),
                    'off' => esc_html__(BackendStrings::getCommonStrings()['no'], 'divi-divi_amelia'),
                ),
                'toggle_slug'     => 'main_content',
                'option_category' => 'basic_option',
            ),
            'categories' => array(
                'label'           => esc_html__(BackendStrings::getWordPressStrings()['select_category'], 'divi-divi_amelia'),
                'type'            => 'select',
                'options'         => $this->categories,
                'toggle_slug'     => 'main_content',
                'option_category' => 'basic_option',
                'show_if'         => array(
                    'booking_params' => 'on',
                ),
            ),
            'services' => array(
                'label'           => esc_html__(BackendStrings::getWordPressStrings()['select_service'], 'divi-divi_amelia'),
                'type'            => 'select',
                'toggle_slug'     => 'main_content',
                'options'         => $this->services,
                'option_category' => 'basic_option',
                'show_if'         => array(
                    'booking_params' => 'on',
                ),
            ),
            'employees' => array(
                'label'           => esc_html__(BackendStrings::getWordPressStrings()['select_employee'], 'divi-divi_amelia'),
                'type'            => 'select',
                'options'         => $this->employees,
                'toggle_slug'     => 'main_content',
                'option_category' => 'basic_option',
                'show_if'         => array(
                    'booking_params' => 'on',
                ),
            ),
            'locations' => array(
                'label'           => esc_html__(BackendStrings::getWordPressStrings()['select_location'], 'divi-divi_amelia'),
                'type'            => 'select',
                'options'         => $this->locations,
                'toggle_slug'     => 'main_content',
                'option_category' => 'basic_option',
                'show_if'         => array(
                    'booking_params' => 'on',
                ),
            ),
        );

        if ($this->showPackages) {
            $array['type'] = array(
                'label'           => esc_html__(BackendStrings::getWordPressStrings()['show_all'], 'divi-divi_amelia'),
                'type'            => 'select',
                'options'         => $this->type,
                'toggle_slug'     => 'main_content',
                'option_category' => 'basic_option',
                'show_if'         => array(
                    'booking_params' => 'on'
            ));
        }

        $array['trigger'] = array(
            'label'           => esc_html__(BackendStrings::getWordPressStrings()['manually_loading'], 'divi-divi_amelia'),
            'type'            => 'text',
            'toggle_slug'     => 'main_content',
            'option_category' => 'basic_option',
            'description'     => BackendStrings::getWordPressStrings()['manually_loading_description'],
        );

        return $array;
    }

    public function checkValues($val)
    {
        if ($val !== null) {
            return !is_numeric($val) ? (strpos($val, 'id:') ?  substr(explode('id: ', $val)[1], 0, -1) : '0') : $val;
        }
        return '0';
    }

    public function render($attrs, $content = null, $render_slug = null)
    {
        $preselect =  $this->props['booking_params'];
        $shortcode = '[ameliabooking';
        $showAll   = isset($this->props['type']) ? $this->props['type'] : null;
        $trigger   = $this->props['trigger'];
        if ($showAll !== null && $showAll !== '' && $showAll !== '0') {
            $shortcode .= ' show='.$showAll;
        }
        if ($trigger !== null && $trigger !== '') {
            $shortcode .= ' trigger='.$trigger;
        }
        if ($preselect === 'on') {
            $category = $this->checkValues($this->props['categories']);
            $service  = $this->checkValues($this->props['services']);
            $employee = $this->checkValues($this->props['employees']);
            $location = $this->checkValues($this->props['locations']);

            if ($service !== '0') {
                $shortcode .= ' service=' . $service;
            } else if ($category !== '0') {
                $shortcode .= ' category=' . $category;
            }
            if ($employee !== '0') {
                $shortcode .= ' employee=' . $employee;
            }
            if ($location !== '0') {
                $shortcode .= ' location=' . $location;
            }
        }
        $shortcode .= ']';
        return do_shortcode($shortcode);
    }
}

new DIVI_Booking;
