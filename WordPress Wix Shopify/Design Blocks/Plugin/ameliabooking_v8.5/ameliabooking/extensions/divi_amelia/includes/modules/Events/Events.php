<?php

use AmeliaBooking\Infrastructure\WP\GutenbergBlock\GutenbergBlock;
use AmeliaBooking\Infrastructure\WP\Translations\BackendStrings;
use AmeliaBooking\Infrastructure\Licence;

class DIVI_Events extends ET_Builder_Module
{

    public $slug       = 'divi_events';
    public $vb_support = 'on';

    public $type    = array();
    private $events = array();
    private $tags   = array();


    protected $module_credits = array(
        'module_uri' => '',
        'author'     => '',
        'author_uri' => '',
    );

    public function init()
    {
        $this->name = esc_html__(BackendStrings::getWordPressStrings()['events_divi'], 'divi-divi_amelia');

        $isLite = !Licence\Licence::$premium;

        $this->type['list']     = BackendStrings::getWordPressStrings()['show_event_view_list'];

        if (!$isLite) {
            $this->type['calendar'] = BackendStrings::getWordPressStrings()['show_event_view_calendar'];
        }

        if (!is_admin()) {
            return;
        }

        $data = GutenbergBlock::getEntitiesData()['data'];

        $this->events['0'] = BackendStrings::getWordPressStrings()['show_all_events'];
        foreach ($data['events'] as $event) {
            $this->events[$event['id']] = $event['name'] . ' (id: ' . $event['id'] . ') - ' . $event['formattedPeriodStart'];
        }
        $this->tags['0'] = BackendStrings::getWordPressStrings()['show_all_tags'];
        foreach ($data['tags'] as $tag) {
            $this->tags[$tag['name']] = $tag['name'] . ' (id: ' . $tag['id'] . ')';
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
        $isLite = !Licence\Licence::$premium;

        $typeArr = array(
            'type' => array(
                'label'           => esc_html__(BackendStrings::getWordPressStrings()['show_event_view_type'], 'divi-divi_amelia'),
                'type'            => 'select',
                'options'         => $this->type,
                'default'         => array_keys($this->type)[0],
                'toggle_slug'     => 'main_content',
                'option_category' => 'basic_option',
            )
        );

        $mainArr = array(
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
                'type'            => 'select',
                'options'         => $this->events,
                'toggle_slug'     => 'main_content',
                'option_category' => 'basic_option',
                'show_if'         => array(
                    'booking_params' => 'on',
                ),
            ),
            'tags' => array(
                'label'           => esc_html__(BackendStrings::getWordPressStrings()['select_tag'], 'divi-divi_amelia'),
                'type'            => 'select',
                'toggle_slug'     => 'main_content',
                'options'         => $this->tags,
                'option_category' => 'basic_option',
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
            'trigger' => array(
                'label'           => esc_html__(BackendStrings::getWordPressStrings()['manually_loading'], 'divi-divi_amelia'),
                'type'            => 'text',
                'toggle_slug'     => 'main_content',
                'option_category' => 'basic_option',
                'description'     => BackendStrings::getWordPressStrings()['manually_loading_description'],
            ),
        );

        if (!$isLite) {
            return array_merge($typeArr, $mainArr);
        } else {
            return $mainArr;
        }
    }

    public function checkValues($val)
    {
        if ($val !== null) {
            $matches = [];
            $id      = preg_match('/id: \d+\)/', $val, $matches);
            return !is_numeric($val) ? ($id && count($matches) ? substr($matches[0], 4, -1) : '0') : $val;
        }
        return '0';
    }

    public function render($attrs, $content = null, $render_slug = null)
    {
        $preselect =  $this->props['booking_params'];
        $shortcode = '[ameliaevents';
        $type      = $this->props['type'];
        $trigger   = $this->props['trigger'];
        if ($type !== null && $type !== '' && $type !== '0') {
            $shortcode .= ' type='.$type;
        }
        if ($trigger !== null && $trigger !== '') {
            $shortcode .= ' trigger='.$trigger;
        }
        if ($preselect === 'on') {
            $event = $this->checkValues($this->props['events']);
            $tag   = $this->props['tags'];
            if ($event !== '0') {
                $shortcode .= ' event=' . $event;
            }
            if ($tag !== null && $tag !== '' && $tag !== '0') {
                $shortcode .= ' tag="' . $tag . '"';
            }
            $recurring = $this->props['recurring'];
            if ($recurring === 'on') {
                $shortcode .= ' recurring=1';
            }
        }
        $shortcode .= ']';

        return do_shortcode($shortcode);
    }
}

new DIVI_Events;
