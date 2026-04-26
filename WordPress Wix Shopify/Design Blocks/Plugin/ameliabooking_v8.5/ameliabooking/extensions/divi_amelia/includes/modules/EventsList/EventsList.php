<?php

use AmeliaBooking\Infrastructure\WP\GutenbergBlock\GutenbergBlock;
use AmeliaBooking\Infrastructure\WP\Translations\BackendStrings;

class DIVI_EventsList extends ET_Builder_Module
{

    public $slug       = 'divi_events_list_booking';
    public $vb_support = 'on';

    private $events = array();
    private $tags   = array();

    private $locations = array();


    protected $module_credits = array(
        'module_uri' => '',
        'author'     => '',
        'author_uri' => '',
    );

    public function init()
    {
        $this->name = esc_html__(BackendStrings::getWordPressStrings()['events_list_booking_divi'], 'divi-divi_amelia');

        if (!is_admin()) {
            return;
        }

        $data = GutenbergBlock::getEntitiesData()['data'];


        foreach ($data['events'] as $event) {
            $this->events[$event['id']] = $event['name'] . ' (id: ' . $event['id'] . ') - ' . $event['formattedPeriodStart'];
        }

        foreach ($data['tags'] as $tag) {
            $this->tags[$tag['name']] = $tag['name'] . ' (id: ' . $tag['id'] . ')';
        }

        foreach ($data['locations'] as $location) {
            $this->locations[$location['id']] = $location['name'];
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
        return array(
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
            'events' => array(
                'label'           => esc_html__(BackendStrings::getWordPressStrings()['select_event'], 'divi-divi_amelia'),
                'type'            => 'amelia_multi_select',
                'showAllText'     => BackendStrings::getWordPressStrings()['show_all_events'],
                'options'         => $this->events,
                'toggle_slug'     => 'main_content',
                'option_category' => 'basic_option',
                'show_if'         => array(
                    'booking_params' => 'on',
                ),
            ),
            'tags' => array(
                'label'           => esc_html__(BackendStrings::getWordPressStrings()['select_tag'], 'divi-divi_amelia'),
                'type'            => 'amelia_multi_select',
                'showAllText'     => BackendStrings::getWordPressStrings()['show_all_tags'],
                'toggle_slug'     => 'main_content',
                'options'         => $this->tags,
                'option_category' => 'basic_option',
                'brackets'        => true,
                'show_if'         => array(
                    'booking_params' => 'on',
                ),
            ),
            'recurring' => array(
                'label'           => esc_html__(BackendStrings::getWordPressStrings()['recurring_event'], 'divi-divi_amelia'),
                'type'            => 'yes_no_button',
                'options' => array(
                    'on'  => esc_html__(BackendStrings::getCommonStrings()['yes'], 'divi-divi_amelia'),
                    'off' => esc_html__(BackendStrings::getCommonStrings()['no'], 'divi-divi_amelia'),
                ),
                'toggle_slug'     => 'main_content',
                'option_category' => 'basic_option',
                'show_if'         => array(
                    'booking_params' => 'on',
                ),
            ),
            'locations' => array(
                'label'           => esc_html__(BackendStrings::getWordPressStrings()['select_location'], 'divi-divi_amelia'),
                'type'            => 'amelia_multi_select',
                'showAllText'     => BackendStrings::getWordPressStrings()['show_all_locations'],
                'toggle_slug'     => 'main_content',
                'options'         => $this->locations,
                'option_category' => 'basic_option',
                'show_if'         => array(
                    'booking_params' => 'on',
                ),
            ),
            'trigger' => array(
                'label'           => esc_html__(BackendStrings::getWordPressStrings()['manually_loading'], 'divi-divi_amelia'),
                'type'            => 'text',
                'toggle_slug'     => 'main_content',
                'option_category' => 'basic_option',
                'description'     => BackendStrings::getWordPressStrings()['manually_loading_description'],
            ),
            'trigger_type' => array(
                'label'           => esc_html__(BackendStrings::getWordPressStrings()['trigger_type'], 'divi-divi_amelia'),
                'type'            => 'select',
                'options'         => array(
                    'id' => BackendStrings::getWordPressStrings()['trigger_type_id'],
                    'class' => BackendStrings::getWordPressStrings()['trigger_type_class']
                ),
                'toggle_slug'     => 'main_content',
                'option_category' => 'basic_option',
            ),
            'in_dialog' => array(
                'label'             => esc_html__(BackendStrings::getWordPressStrings()['in_dialog'], 'divi-divi_amelia'),
                'type'              => 'yes_no_button',
                'options'           => array(
                    'on'  => esc_html__(BackendStrings::getCommonStrings()['yes'], 'divi-divi_amelia'),
                    'off' => esc_html__(BackendStrings::getCommonStrings()['no'], 'divi-divi_amelia'),
                ),
                'toggle_slug'     => 'main_content',
                'option_category' => 'basic_option',
            ),
        );
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
        $preselect =  $this->props['booking_params'];
        $shortcode = '[ameliaeventslistbooking';
        $trigger   = $this->props['trigger'];
        $trigger_type = $this->props['trigger_type'];
        $in_dialog = $this->props['in_dialog'];
        if ($trigger !== null && $trigger !== '') {
            $shortcode .= ' trigger='.$trigger;
        }
        if (!empty($trigger) && !empty($trigger_type)) {
            $shortcode .= ' trigger_type='.$trigger_type;
        }
        if (!empty($trigger) && $in_dialog === 'on') {
            $shortcode .= ' in_dialog=1';
        }
        if ($preselect === 'on') {
            $event = $this->checkValues($this->props['events']);
            $tag   = $this->props['tags'];
            if ($event && count($event) > 0) {
                $shortcode .= ' event=' . implode(',', $event);
            }
            if ($tag) {
                $shortcode .= ' tag="' . $tag . '"';
            }
            $recurring = $this->props['recurring'];
            if ($recurring === 'on') {
                $shortcode .= ' recurring=1';
            }
            $locations = $this->checkValues($this->props['locations']);
            if ($locations && count($locations) > 0) {
                $shortcode .= ' location=' . implode(',', $locations);
            }
        }
        $shortcode .= ']';

        return do_shortcode($shortcode);
    }
}

new DIVI_EventsList;
