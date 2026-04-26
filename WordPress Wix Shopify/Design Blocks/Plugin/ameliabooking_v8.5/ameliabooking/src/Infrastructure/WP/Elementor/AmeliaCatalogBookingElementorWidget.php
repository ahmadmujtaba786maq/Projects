<?php

/**
 * @copyright © TMS-Plugins. All rights reserved.
 * @licence   See LICENCE.md for license details.
 */

namespace Elementor;

use AmeliaBooking\Infrastructure\WP\GutenbergBlock\GutenbergBlock;
use AmeliaBooking\Infrastructure\WP\Translations\BackendStrings;

/**
 * Class AmeliaCatalogBookingElementorWidget
 *
 * @package AmeliaBooking\Infrastructure\WP\Elementor
 */
class AmeliaCatalogBookingElementorWidget extends Widget_Base
{
    protected $controls_data;

    public function get_name()
    {
        return 'catalogbooking';
    }

    public function get_title()
    {
        return BackendStrings::getWordPressStrings()['catalog_booking_gutenberg_block']['title'];
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
            'amelia_catalog_section',
            [
                'label' => '<div class="amelia-elementor-content"><p class="amelia-elementor-content-title">'
                    . BackendStrings::getWordPressStrings()['catalog_booking_gutenberg_block']['title']
                    . '</p><br><p class="amelia-elementor-content-p">'
                    . BackendStrings::getWordPressStrings()['catalog_booking_gutenberg_block']['description']
                    . '</p>',
            ]
        );

        $options = [
            'show_catalog' => BackendStrings::getWordPressStrings()['show_catalog'],
            'show_category' => BackendStrings::getWordPressStrings()['show_categories'],
            'show_service' => BackendStrings::getWordPressStrings()['show_services'],
        ];

        if ($controls_data['packages']) {
            $options['show_package'] = BackendStrings::getWordPressStrings()['show_packages'];
        }

        if ($controls_data['categories'] && sizeof($controls_data['locations']) > 1) {
        }

        $this->add_control(
            'select_catalog',
            [
                'label' => BackendStrings::getWordPressStrings()['select_catalog_view'],
                'type' => Controls_Manager::SELECT,
                'label_block' => true,
                'options' => $options,
                'default' => 'show_catalog',
            ]
        );

        $this->add_control(
            'select_category',
            [
                'label' => BackendStrings::getWordPressStrings()['select_category'],
                'type' => Controls_Manager::SELECT2,
                'multiple' => true,
                'options' => $controls_data['categories'],
                'condition' => ['select_catalog' => 'show_category'],
                'default' => array_keys($controls_data['categories']) ? [array_keys($controls_data['categories'])[0]] : 0,
            ]
        );

        if ($controls_data['services'] && sizeof($controls_data['services']) > 1) {
            $this->add_control(
                'select_service',
                [
                    'label' => BackendStrings::getWordPressStrings()['select_service'],
                    'type' => Controls_Manager::SELECT2,
                    'multiple' => true,
                    'options' => $controls_data['services'],
                    'condition' => ['select_catalog' => 'show_service'],
                    'default' => array_keys($controls_data['services']) ? [array_keys($controls_data['services'])[0]] : 0,
                ]
            );
        }

        if ($controls_data['packages']) {
            $this->add_control(
                'select_package',
                [
                    'label' => BackendStrings::getWordPressStrings()['select_package'],
                    'type' => Controls_Manager::SELECT2,
                    'multiple' => true,
                    'options' => $controls_data['packages'],
                    'condition' => ['select_catalog' => 'show_package'],
                    'default' => array_keys($controls_data['packages']) ? [array_keys($controls_data['packages'])[0]] : 0,
                ]
            );
        }

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
            'skip_categories',
            [
                'label' => BackendStrings::getWordPressStrings()['skip_categories'],
                'type' => Controls_Manager::SWITCHER,
                'default' => false,
                'label_on' => BackendStrings::getCommonStrings()['yes'],
                'label_off' => BackendStrings::getCommonStrings()['no'],
                'condition' => ['preselect' => 'yes'],
            ]
        );

        if ($controls_data['employees'] && sizeof($controls_data['employees']) > 1) {
            $this->add_control(
                'select_employee',
                [
                    'label' => BackendStrings::getWordPressStrings()['select_employee'],
                    'type' => Controls_Manager::SELECT2,
                    'multiple' => true,
                    'options' => $controls_data['employees'],
                    'condition' => ['preselect' => 'yes'],
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

        if ($settings['select_catalog'] === 'show_package') {
            $this->remove_control('select_show');
        }

        $skip_categories = $settings['skip_categories'] === 'yes' ? ' categories_hidden=1' : '';

        $trigger      = $settings['load_manually'] !== '' ? ' trigger=' . $settings['load_manually'] : '';
        $trigger_type = $settings['load_manually'] && $settings['trigger_type'] !== '' ? ' trigger_type=' . $settings['trigger_type'] : '';
        $in_dialog    = $settings['load_manually'] && $settings['in_dialog'] === 'yes' ? ' in_dialog=1' : '';

        $show = '';

        if ($settings['select_catalog'] === 'show_catalog') {
            $category_service = '';

            $show = empty($settings['select_show']) ? '' : ' show=' . $settings['select_show'];
        } elseif ($settings['select_catalog'] === 'show_category' && !empty($settings['select_category'])) {
            $category_service = ' category=' . (is_array($settings['select_category']) ?
                    implode(',', $settings['select_category']) : $settings['select_category']);

            $show = empty($settings['select_show']) ? '' : ' show=' . $settings['select_show'];
        } elseif ($settings['select_catalog'] === 'show_service' && !empty($settings['select_service'])) {
            $category_service = ' service=' . (is_array($settings['select_service']) ?
                    implode(',', $settings['select_service']) : $settings['select_service']);

            $show = empty($settings['select_show']) || $settings['select_show'] === 'packages' ? '' : ' show=' . $settings['select_show'];
        } elseif ($settings['select_catalog'] === 'show_package' && !empty($settings['select_package'])) {
            $category_service = ' package=' . (is_array($settings['select_package']) ?
                    implode(',', $settings['select_package']) : $settings['select_package']);
        } else {
            $category_service = '';
        }

        if ($settings['preselect']) {
            $employee = empty($settings['select_employee']) ? '' : ' employee=' . (is_array($settings['select_employee']) ?
                    implode(',', $settings['select_employee']) : $settings['select_employee']);
            $location = empty($settings['select_location']) ? '' : ' location=' . (is_array($settings['select_location']) ?
                    implode(',', $settings['select_location']) : $settings['select_location']);
        } else {
            $employee = '';
            $location = '';
        }
        echo esc_html('[ameliacatalogbooking' .
            $show .
            $trigger .
            $trigger_type .
            $in_dialog .
            $category_service .
            $employee .
            $location .
            $skip_categories . ']');
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

        $elementorData['packages'] = [];

        foreach ($data['packages'] as $package) {
            $elementorData['packages'][$package['id']] = $package['name'] . ' (id: ' . $package['id'] . ')';
        }

        $elementorData['employees'] = [];
        foreach ($data['employees'] as $provider) {
            $elementorData['employees'][$provider['id']] = $provider['firstName'] . $provider['lastName'] . ' (id: ' . $provider['id'] . ')';
        }

        $elementorData['locations'] = [];
        foreach ($data['locations'] as $location) {
            $elementorData['locations'][$location['id']] = $location['name'] . ' (id: ' . $location['id'] . ')';
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

        return $elementorData;
    }
}
