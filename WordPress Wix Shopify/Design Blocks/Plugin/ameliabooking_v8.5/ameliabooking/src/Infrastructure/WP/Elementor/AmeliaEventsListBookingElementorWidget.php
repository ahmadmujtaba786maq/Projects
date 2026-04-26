<?php

/**
 * @copyright © TMS-Plugins. All rights reserved.
 * @licence   See LICENCE.md for license details.
 */

namespace Elementor;

use AmeliaBooking\Infrastructure\WP\GutenbergBlock\GutenbergBlock;
use AmeliaBooking\Infrastructure\WP\Translations\BackendStrings;

/**
 * Class AmeliaEventsListBookingElementorWidget
 *
 * @package AmeliaBooking\Infrastructure\WP\Elementor
 */
class AmeliaEventsListBookingElementorWidget extends Widget_Base
{
    public function get_name()
    {
        return 'ameliaeventslistbooking';
    }

    public function get_title()
    {
        return BackendStrings::getWordPressStrings()['events_list_booking_gutenberg_block']['title'];
    }

    public function get_icon()
    {
        return 'amelia-logo';
    }

    public function get_categories()
    {
        return [ 'amelia-elementor' ];
    }
    protected function register_controls()
    {

        $this->start_controls_section(
            'amelia_events_section',
            [
                'label' => '<div class="amelia-elementor-content"><p class="amelia-elementor-content-title">'
                    . BackendStrings::getWordPressStrings()['events_list_booking_gutenberg_block']['title']
                    . '</p><br><p class="amelia-elementor-content-p">'
                    . BackendStrings::getWordPressStrings()['events_list_booking_gutenberg_block']['description']
                    . '</p>',
            ]
        );

        $this->add_control(
            'preselect',
            [
                'label' => BackendStrings::getWordPressStrings()['filter'],
                'type' => Controls_Manager::SWITCHER,
                'default' => false,
                'label_on' => BackendStrings::getCommonStrings()['yes'],
                'label_off' => BackendStrings::getCommonStrings()['no'],
            ]
        );

        $this->add_control(
            'select_event',
            [
                'label' => BackendStrings::getWordPressStrings()['select_event'],
                'type' => Controls_Manager::SELECT2,
                'multiple' => true,
                'options' => self::amelia_elementor_get_events(),
                'condition' => ['preselect' => 'yes'],
                'placeholder' => BackendStrings::getWordPressStrings()['show_all_events']
            ]
        );

        $this->add_control(
            'select_tag',
            [
                'label' => BackendStrings::getWordPressStrings()['select_tag'],
                'type' => Controls_Manager::SELECT2,
                'multiple' => true,
                'options' => self::amelia_elementor_get_tags(),
                'condition' => ['preselect' => 'yes'],
                'placeholder' => BackendStrings::getWordPressStrings()['show_all_tags']
            ]
        );


        $this->add_control(
            'select_location',
            [
                'label' => BackendStrings::getWordPressStrings()['select_location'],
                'type' => Controls_Manager::SELECT2,
                'multiple' => true,
                'options' => self::amelia_elementor_get_locations(),
                'condition' => ['preselect' => 'yes'],
                'placeholder' => BackendStrings::getWordPressStrings()['show_all_locations']
            ]
        );

        $this->add_control(
            'show_recurring',
            [
                'label' => __('Show recurring events:'),
                'type' => Controls_Manager::SWITCHER,
                'condition' => ['preselect' => 'yes'],
                'default' => false,
                'label_on' => BackendStrings::getCommonStrings()['yes'],
                'label_off' => BackendStrings::getCommonStrings()['no'],
            ]
        );

        $this->add_control(
            'load_manually',
            [
                'label' => BackendStrings::getWordPressStrings()['manually_loading'],
                'label_block' => true,
                'type' => Controls_Manager::TEXT,
                'condition' => ['preselect' => 'yes'],
                'placeholder' => '',
                'description' => BackendStrings::getWordPressStrings()['manually_loading_description'],
            ]
        );

        $this->add_control(
            'trigger_type',
            [
                'label' => BackendStrings::getWordPressStrings()['trigger_type'],
                'type' => Controls_Manager::SELECT,
                'condition' => ['preselect' => 'yes'],
                'description' => BackendStrings::getWordPressStrings()['trigger_type_tooltip'],
                'options' => [
                    'id' => BackendStrings::getWordPressStrings()['trigger_type_id'],
                    'class' => BackendStrings::getWordPressStrings()['trigger_type_class']
                ],
                'default' => 'id'
            ]
        );

        $this->add_control(
            'in_dialog',
            [
                'label' => BackendStrings::getWordPressStrings()['in_dialog'],
                'type' => Controls_Manager::SWITCHER,
                'condition' => ['preselect' => 'yes'],
                'default' => false,
                'label_on' => BackendStrings::getCommonStrings()['yes'],
                'label_off' => BackendStrings::getCommonStrings()['no'],
            ]
        );

        $this->end_controls_section();
    }

    protected function render()
    {

        $settings = $this->get_settings_for_display();

        if ($settings['preselect']) {
            $trigger      = $settings['load_manually'] !== '' ? ' trigger=' . $settings['load_manually'] : '';
            $trigger_type = $settings['load_manually'] && $settings['trigger_type'] !== '' ? ' trigger_type=' . $settings['trigger_type'] : '';
            $in_dialog    = $settings['load_manually'] && $settings['in_dialog'] === 'yes' ? ' in_dialog=1' : '';

            $selected_event = empty($settings['select_event']) ? '' : ' event=' . (is_array($settings['select_event']) ?
                    implode(',', $settings['select_event']) : $settings['select_event']);

            $show_recurring = $settings['show_recurring'] ? ' recurring=1' : '';

            $selected_location = empty($settings['select_location']) ? '' : ' location=' . (is_array($settings['select_location']) ?
                    implode(',', $settings['select_location']) : $settings['select_location']);

            $selected_tag = '';
            if (!empty($settings['select_tag'])) {
                $selected_tag .= ' tag="';
                if (is_array($settings['select_tag'])) {
                    foreach (array_filter($settings['select_tag']) as $index => $tag) {
                        $selected_tag .= ($index === 0 ? '' : ',') . '{' . $tag . '}';
                    }
                } else {
                    $selected_tag .= $settings['select_tag'];
                }
                $selected_tag .= '"';
            }

            echo '[ameliaeventslistbooking' .
                $trigger .
                $trigger_type .
                $in_dialog .
                $selected_event .
                $selected_location .
                $selected_tag .
                $show_recurring . ']';
        } else {
            echo '[ameliaeventslistbooking]';
        }
    }


    public static function amelia_elementor_get_events()
    {
        $events = GutenbergBlock::getEntitiesData()['data']['events'];

        $returnEvents = [];

        $returnEvents['0'] = BackendStrings::getWordPressStrings()['show_all_events'];

        foreach ($events as $event) {
            $returnEvents[$event['id']] = $event['name'] . ' (id: ' . $event['id'] . ') - ' . $event['formattedPeriodStart'];
        }

        return $returnEvents;
    }

    public static function amelia_elementor_get_locations()
    {
        $locations = GutenbergBlock::getEntitiesData()['data']['locations'];

        $returnLocations = [];

        $returnLocations['0'] = BackendStrings::getWordPressStrings()['show_all_locations'];

        foreach ($locations as $location) {
            $returnLocations[$location['id']] = $location['name'];
        }

        return $returnLocations;
    }

    public static function amelia_elementor_get_tags()
    {
        $tags = GutenbergBlock::getEntitiesData()['data']['tags'];

        $returnTags = [];

        foreach ($tags as $index => $tag) {
            $returnTags[$tag['name']] = $tag['name'];
        }

        return $returnTags;
    }
}
