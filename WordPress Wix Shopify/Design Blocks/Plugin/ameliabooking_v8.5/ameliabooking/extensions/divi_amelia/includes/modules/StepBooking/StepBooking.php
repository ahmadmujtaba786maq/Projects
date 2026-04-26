<?php

use AmeliaBooking\Infrastructure\WP\GutenbergBlock\GutenbergBlock;
use AmeliaBooking\Infrastructure\WP\Translations\BackendStrings;

class DIVI_StepBooking extends ET_Builder_Module
{

    public $slug       = 'divi_step_booking';
    public $vb_support = 'on';

    private $categories = array();
    private $services   = array();
    private $employees  = array();
    private $locations  = array();
    private $packages   = array();
    private $showPackages = true;

    public $type = array();
    private $trigger_types = array();
    private $layout_options = array();

    protected $module_credits = array(
        'module_uri' => '',
        'author'     => '',
        'author_uri' => '',
    );

    public function init()
    {
        $this->name = esc_html__(BackendStrings::getWordPressStrings()['step_booking_divi'], 'divi-divi_amelia');

        $this->type['0']        = BackendStrings::getWordPressStrings()['show_all'];
        $this->type['services'] = BackendStrings::getCommonStrings()['services'];
        $this->type['packages'] = BackendStrings::getCommonStrings()['packages'];

        // Initialize layout options
        $this->layout_options = [
            '1' => BackendStrings::getCommonStrings()['layout_dropdown'],
            '2' => BackendStrings::getCommonStrings()['layout_list']
        ];

        if (!is_admin()) {
            return;
        }

        $this->trigger_types = [
            'id' => BackendStrings::getWordPressStrings()['trigger_type_id'],
            'class' => BackendStrings::getWordPressStrings()['trigger_type_class']
        ];

        $data = GutenbergBlock::getEntitiesData()['data'];
        $this->showPackages = !empty($data['packages']);

//        $this->categories['0'] = BackendStrings::getWordPressStrings()['show_all_categories'];
        foreach ($data['categories'] as $category) {
            $this->categories[$category['id']] = $category['name']. ' (id: ' . $category['id'] . ')';
        }
        foreach ($data['servicesList'] as $service) {
            if ($service) {
                $this->services[$service['id']] = $service['name']. ' (id: ' . $service['id'] . ')';
            }
        }
        foreach ($data['employees'] as $employee) {
            $this->employees[$employee['id']] = $employee['firstName'] . ' ' . $employee['lastName'] . ' (id: ' . $employee['id'] . ')';
        }
        foreach ($data['locations'] as $location) {
            $this->locations[$location['id']] = $location['name']. ' (id: ' . $location['id'] . ')';
        }
        foreach ($data['packages'] as $package) {
            $this->packages[$package['id']] = $package['name']. ' (id: ' . $package['id'] . ')';
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
            )
        );

        $array['categories'] = array(
            'label'           => esc_html__(BackendStrings::getWordPressStrings()['select_category'], 'divi-divi_amelia'),
            'type'            => 'amelia_multi_select',
            'showAllText'     => BackendStrings::getWordPressStrings()['show_all_categories'],
            'options'         => $this->categories,
            'toggle_slug'     => 'main_content',
            'option_category' => 'basic_option',
            'show_if'         => array(
                'booking_params' => 'on',
            ),
        );

        $array['services'] = array(
            'label'           => esc_html__(BackendStrings::getWordPressStrings()['select_service'], 'divi-divi_amelia'),
            'type'            => 'amelia_multi_select',
            'toggle_slug'     => 'main_content',
            'showAllText'     => BackendStrings::getWordPressStrings()['show_all_services'],
            'options'         => $this->services,
            'option_category' => 'basic_option',
            'show_if'         => array(
                'booking_params' => 'on',
            ),
        );

        $array['employees'] = array(
            'label'           => esc_html__(BackendStrings::getWordPressStrings()['select_employee'], 'divi-divi_amelia'),
            'type'            => 'amelia_multi_select',
            'options'         => $this->employees,
            'toggle_slug'     => 'main_content',
            'showAllText'     => BackendStrings::getWordPressStrings()['show_all_employees'],
            'option_category' => 'basic_option',
            'show_if'         => array(
                'booking_params' => 'on',
            ),
        );

        $array['locations'] = array(
            'label'           => esc_html__(BackendStrings::getWordPressStrings()['select_location'], 'divi-divi_amelia'),
            'type'            => 'amelia_multi_select',
            'options'         => $this->locations,
            'showAllText'     => BackendStrings::getWordPressStrings()['show_all_locations'],
            'toggle_slug'     => 'main_content',
            'option_category' => 'basic_option',
            'show_if'         => array(
                'booking_params' => 'on',
            ),
        );


        if ($this->showPackages) {
            $array['packages'] = array(
                'label'           => esc_html__(BackendStrings::getWordPressStrings()['select_package'], 'divi-divi_amelia'),
                'type'            => 'amelia_multi_select',
                'options'         => $this->packages,
                'toggle_slug'     => 'main_content',
                'option_category' => 'basic_option',
                'showAllText'     => BackendStrings::getWordPressStrings()['show_all_packages'],
                'show_if'         => array(
                    'booking_params' => 'on',
                ),
            );

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

        $array['layout'] = array(
            'label'           => esc_html__(BackendStrings::getCommonStrings()['layout_select_label'], 'divi-divi_amelia'),
            'type'            => 'select',
            'options'         => $this->layout_options,
            'toggle_slug'     => 'main_content',
            'option_category' => 'basic_option',
            'default'         => '1',
        );

        $array['trigger'] = array(
            'label'           => esc_html__(BackendStrings::getWordPressStrings()['manually_loading'], 'divi-divi_amelia'),
            'type'            => 'text',
            'toggle_slug'     => 'main_content',
            'option_category' => 'basic_option',
            'description'     => BackendStrings::getWordPressStrings()['manually_loading_description'],
        );

        $array['trigger_type'] = array(
            'label'           => esc_html__(BackendStrings::getWordPressStrings()['trigger_type'], 'divi-divi_amelia'),
            'type'            => 'select',
            'options'         => $this->trigger_types,
            'toggle_slug'     => 'main_content',
            'option_category' => 'basic_option',
        );

        $array['in_dialog'] = array(
            'label'             => esc_html__(BackendStrings::getWordPressStrings()['in_dialog'], 'divi-divi_amelia'),
            'type'              => 'yes_no_button',
            'options'           => array(
                'on'  => esc_html__(BackendStrings::getCommonStrings()['yes'], 'divi-divi_amelia'),
                'off' => esc_html__(BackendStrings::getCommonStrings()['no'], 'divi-divi_amelia'),
            ),
            'toggle_slug'     => 'main_content',
            'option_category' => 'basic_option',
        );

        return $array;
    }

    public function checkValues($val)
    {
        if ($val !== null) {
            $val = explode(',', $val);
            if (is_array($val)) {
                $newVals = [];
                foreach ($val as $parameter) {
                    if ($parameter) {
                        $newVals[] = !is_numeric($parameter) ? (strpos($parameter, 'id:') ?  substr(explode('id: ', $parameter)[1], 0, -1) : $parameter) : $parameter;
                    }
                }
                return count($newVals) > 0 ? $newVals : [];
            }
            return [];
        }
        return [];
    }

    public function render($attrs, $content = null, $render_slug = null)
    {
        $preselect    =  $this->props['booking_params'];
        $shortcode    = '[ameliastepbooking';
        $showAll      = isset($this->props['type']) ? $this->props['type'] : null;
        $trigger      = $this->props['trigger'];
        $trigger_type = $this->props['trigger_type'];
        $in_dialog    = $this->props['in_dialog'];
        $layout       = isset($this->props['layout']) ? $this->props['layout'] : '1'; // Default to dropdown layout

        if ($showAll !== null && $showAll !== '' && $showAll !== '0') {
            $shortcode .= ' show='.$showAll;
        }
        if ($trigger !== null && $trigger !== '') {
            $shortcode .= ' trigger='.$trigger;
        }
        if (!empty($trigger) && !empty($trigger_type)) {
            $shortcode .= ' trigger_type='.$trigger_type;
        }
        if (!empty($trigger) && $in_dialog === 'on') {
            $shortcode .= ' in_dialog=1';
        }

        // Add layout parameter to the shortcode
        $shortcode .= ' layout='.$layout;

        if ($preselect === 'on') {
            $category = !empty($this->props['categories']) ? $this->checkValues($this->props['categories']) : null;
            $service  = !empty($this->props['services']) ? $this->checkValues($this->props['services']) : null;
            $employee = !empty($this->props['employees']) ? $this->checkValues($this->props['employees']) : null;
            $location = !empty($this->props['locations']) ? $this->checkValues($this->props['locations']) : null;
            $package  = !empty($this->props['packages']) ? $this->checkValues($this->props['packages']) : null;

            if ($service && count($service) > 0) {
                $shortcode .= ' service=' . implode(',', $service);
            } else if ($category && count($category) > 0) {
                $shortcode .= ' category=' . implode(',', $category);
            }
            if ($employee && count($employee) > 0) {
                $shortcode .= ' employee=' . implode(',', $employee);
            }
            if ($location && count($location) > 0) {
                $shortcode .= ' location=' . implode(',', $location);
            }
            if ($package && count($package) > 0) {
                $shortcode .= ' package=' . implode(',', $package);
            }
        }
        $shortcode .= ']';

        return do_shortcode($shortcode);
    }
}

new DIVI_StepBooking;
