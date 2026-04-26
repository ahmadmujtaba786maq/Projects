<?php

/**
 * @copyright © TMS-Plugins. All rights reserved.
 * @licence   See LICENCE.md for license details.
 */

namespace Elementor;

use AmeliaBooking\Infrastructure\WP\GutenbergBlock\GutenbergBlock;
use AmeliaBooking\Infrastructure\WP\Translations\BackendStrings;

/**
 * Class AmeliaStepBookingElementorWidget
 *
 * @package AmeliaBooking\Infrastructure\WP\Elementor
 */
class AmeliaStepBookingElementorWidget extends Widget_Base
{
    protected $controls_data;

    public function get_name()
    {
        return 'stepbooking';
    }

    public function get_title()
    {
        return BackendStrings::getWordPressStrings()['step_booking_gutenberg_block']['title'];
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

        $controls_data = self::amelia_elementor_get_data();

        $this->start_controls_section(
            'amelia_booking_section',
            [
                'label' => '<div class="amelia-elementor-content"><p class="amelia-elementor-content-title">'
                    . BackendStrings::getWordPressStrings()['step_booking_gutenberg_block']['title']
                    . '</p><br><p class="amelia-elementor-content-p">'
                    . BackendStrings::getWordPressStrings()['step_booking_gutenberg_block']['description']
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

        if ($controls_data['categories'] && sizeof($controls_data['categories']) > 1) {
            $this->add_control(
                'select_category',
                [
                    'label' => BackendStrings::getWordPressStrings()['select_category'],
                    'type' => Controls_Manager::SELECT2,
                    'multiple' => true,
                    'options' => $controls_data['categories'],
                    'condition' => ['preselect' => 'yes'],
                    'placeholder' => BackendStrings::getWordPressStrings()['show_all_categories']
                ]
            );
        }

        if ($controls_data['services'] && sizeof($controls_data['services']) > 1) {
            $this->add_control(
                'select_service',
                [
                    'label' => BackendStrings::getWordPressStrings()['select_service'],
                    'type' => Controls_Manager::SELECT2,
                    'multiple' => true,
                    'options' => $controls_data['services'],
                    'condition' => ['preselect' => 'yes'],
                    'placeholder' => BackendStrings::getWordPressStrings()['show_all_services'],
                ]
            );
        }

        if ($controls_data['employees'] && sizeof($controls_data['employees']) > 1) {
            $this->add_control(
                'select_employee',
                [
                    'label' => BackendStrings::getWordPressStrings()['select_employee'],
                    'type' => Controls_Manager::SELECT2,
                    'multiple' => true,
                    'options' => $controls_data['employees'],
                    'condition' => ['preselect' => 'yes'],
                    'placeholder' => BackendStrings::getWordPressStrings()['show_all_employees'],
                ]
            );
        }

        if ($controls_data['locations'] && sizeof($controls_data['locations']) > 1) {
            $this->add_control(
                'select_location',
                [
                    'label' => BackendStrings::getWordPressStrings()['select_location'],
                    'type' => Controls_Manager::SELECT2,
                    'multiple' => true,
                    'options' => $controls_data['locations'],
                    'condition' => ['preselect' => 'yes'],
                    'placeholder' => BackendStrings::getWordPressStrings()['show_all_locations'],
                ]
            );
        }

        if ($controls_data['show']) {
            $this->add_control(
                'select_package',
                [
                    'label' => BackendStrings::getWordPressStrings()['select_package'],
                    'type' => Controls_Manager::SELECT2,
                    'multiple' => true,
                    'options' => $controls_data['packages'],
                    'condition' => ['preselect' => 'yes'],
                    'placeholder' => BackendStrings::getWordPressStrings()['show_all_packages'],
                ]
            );
        }

        if ($controls_data['show']) {
            $this->add_control(
                'select_show',
                [
                    'label' => BackendStrings::getWordPressStrings()['show_all'],
                    'type' => Controls_Manager::SELECT,
                    'options' => $controls_data['show'],
                    'condition' => ['preselect' => 'yes'],
                    'default' => '',
                ]
            );
        }

        $this->add_control(
            'layout',
            [
                'label' => BackendStrings::getCommonStrings()['layout_select_label'],
                'type' => Controls_Manager::SELECT,
                'description' => BackendStrings::getCommonStrings()['layout_description'],
                'options' => $controls_data['layout_options'],
                'default' => '1',
            ]
        );

        $this->add_control(
            'load_manually',
            [
                'label' => BackendStrings::getWordPressStrings()['manually_loading'],
                'label_block' => true,
                'type' => Controls_Manager::TEXT,
                'placeholder' => '',
                'description' => BackendStrings::getWordPressStrings()['manually_loading_description'],
            ]
        );

        $this->add_control(
            'trigger_type',
            [
                'label' => BackendStrings::getWordPressStrings()['trigger_type'],
                'type' => Controls_Manager::SELECT,
                'description' => BackendStrings::getWordPressStrings()['trigger_type_tooltip'],
                'options' => $controls_data['trigger_types'],
                'default' => 'id'
            ]
        );

        $this->add_control(
            'in_dialog',
            [
                'label' => BackendStrings::getWordPressStrings()['in_dialog'],
                'type' => Controls_Manager::SWITCHER,
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

        $trigger      = $settings['load_manually'] !== '' ? ' trigger=' . $settings['load_manually'] : '';
        $trigger_type = $settings['load_manually'] && $settings['trigger_type'] !== '' ? ' trigger_type=' . $settings['trigger_type'] : '';
        $in_dialog    = $settings['load_manually'] && $settings['in_dialog'] === 'yes' ? ' in_dialog=1' : '';

        $category = empty($settings['select_category']) ? '' : ' category=' . (is_array($settings['select_category']) ?
                implode(',', $settings['select_category']) : $settings['select_category']);
        $service  = empty($settings['select_service']) ? '' : ' service=' . (is_array($settings['select_service']) ?
                implode(',', $settings['select_service']) : $settings['select_service']);

        $category_service = empty($settings['select_service']) ? $category : $service;

        $employee = empty($settings['select_employee']) ? '' : ' employee=' . (is_array($settings['select_employee']) ?
                implode(',', $settings['select_employee']) : $settings['select_employee']);
        $location = empty($settings['select_location']) ? '' : ' location=' . (is_array($settings['select_location']) ?
                implode(',', $settings['select_location']) : $settings['select_location']);
        $package  = empty($settings['select_package'])  ? '' : ' package=' .  (is_array($settings['select_package']) ?
                implode(',', $settings['select_package']) : $settings['select_package']);

        $show = empty($settings['select_show']) ? '' : ' show=' . $settings['select_show'];

        $layout = $settings['layout'] && $settings['layout'] !== '' ? ' layout=' . $settings['layout'] : '';

        $shortcode = '[ameliastepbooking' . $trigger . $trigger_type . $in_dialog . $layout;
        if ($settings['preselect']) {
            echo $shortcode . $show . $category_service . $employee . $location . $package . ']';
        } else {
            echo $shortcode . ']';
        }
    }


    public static function amelia_elementor_get_data()
    {
        $data          = GutenbergBlock::getEntitiesData()['data'];
        $elementorData = [];

        $elementorData['categories'] = [];

        foreach ($data['categories'] as $category) {
            $elementorData['categories'][$category['id']] = $category['name'] . ' (id: ' . $category['id'] . ')';
        }

        $elementorData['services'] = [];

        foreach ($data['servicesList'] as $service) {
            if ($service) {
                $elementorData['services'][$service['id']] = $service['name'] . ' (id: ' . $service['id'] . ')';
            }
        }

        $elementorData['employees'] = [];

        foreach ($data['employees'] as $provider) {
            $elementorData['employees'][$provider['id']] = $provider['firstName'] . $provider['lastName'] . ' (id: ' . $provider['id'] . ')';
        }

        $elementorData['locations'] = [];

        foreach ($data['locations'] as $location) {
            $elementorData['locations'][$location['id']] = $location['name'] . ' (id: ' . $location['id'] . ')';
        }

        $elementorData['packages'] = [];

        foreach ($data['packages'] as $package) {
            $elementorData['packages'][$package['id']] = $package['name'] . ' (id: ' . $package['id'] . ')';
        }


        $elementorData['show'] = $data['packages'] ? [
            '' => BackendStrings::getWordPressStrings()['show_all'],
            'services' => BackendStrings::getCommonStrings()['services'],
            'packages' => BackendStrings::getCommonStrings()['packages']
        ] : [];

        $elementorData['trigger_types'] = [
            'id' => BackendStrings::getWordPressStrings()['trigger_type_id'],
            'class' => BackendStrings::getWordPressStrings()['trigger_type_class']
        ];

        $elementorData['layout_options'] = [
            '1' => BackendStrings::getCommonStrings()['layout_dropdown'],
            '2' => BackendStrings::getCommonStrings()['layout_list']
        ];

        return $elementorData;
    }
}
