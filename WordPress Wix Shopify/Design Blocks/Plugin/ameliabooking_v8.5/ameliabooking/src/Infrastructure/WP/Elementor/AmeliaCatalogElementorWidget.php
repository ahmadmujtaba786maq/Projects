<?php

/**
 * @copyright © TMS-Plugins. All rights reserved.
 * @licence   See LICENCE.md for license details.
 */

namespace Elementor;

use AmeliaBooking\Infrastructure\WP\GutenbergBlock\GutenbergBlock;
use AmeliaBooking\Infrastructure\WP\Translations\BackendStrings;

/**
 * Class AmeliaCatalogElementorWidget
 *
 * @package AmeliaBooking\Infrastructure\WP\Elementor
 */
class AmeliaCatalogElementorWidget extends Widget_Base
{
    protected $controls_data;

    public function get_name()
    {
        return 'ameliacatalog';
    }

    protected function register_controls()
    {
        $controls_data = self::amelia_elementor_get_data();

        $this->start_controls_section(
            'amelia_catalog_section',
            [
                'label' => '<div class="amelia-elementor-content-outdated"><p class="amelia-elementor-content-title">'
                    . BackendStrings::getWordPressStrings()['catalog_gutenberg_block']['title']
                    . '</p><br><p class="amelia-elementor-content-p">'
                    . BackendStrings::getWordPressStrings()['catalog_gutenberg_block']['description']
                    . '</p><br><p class="amelia-elementor-content-p amelia-elementor-content-p-outdated">'
                    . BackendStrings::getWordPressStrings()['outdated_booking_gutenberg_block']
                    . '</p>',
            ]
        );

        $options = [
            'show_catalog' => BackendStrings::getWordPressStrings()['show_catalog'],
            'show_category' => BackendStrings::getWordPressStrings()['show_category'],
            'show_service' => BackendStrings::getWordPressStrings()['show_service'],
        ];

        if ($controls_data['packages']) {
            $options['show_package'] = BackendStrings::getWordPressStrings()['show_package'];
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
                'type' => Controls_Manager::SELECT,
                'options' => $controls_data['categories'],
                'condition' => ['select_catalog' => 'show_category'],
                'default' => array_keys($controls_data['categories']) ? array_keys($controls_data['categories'])[0] : 0,
            ]
        );

        $this->add_control(
            'select_service',
            [
                'label' => BackendStrings::getWordPressStrings()['select_service'],
                'type' => Controls_Manager::SELECT,
                'options' => $controls_data['services'],
                'condition' => ['select_catalog' => 'show_service'],
                'default' => array_keys($controls_data['services']) ? array_keys($controls_data['services'])[0] : 0,
            ]
        );

        if ($controls_data['packages']) {
            $this->add_control(
                'select_package',
                [
                    'label' => BackendStrings::getWordPressStrings()['select_package'],
                    'type' => Controls_Manager::SELECT,
                    'options' => $controls_data['packages'],
                    'condition' => ['select_catalog' => 'show_package'],
                    'default' => array_keys($controls_data['packages']) ? array_keys($controls_data['packages'])[0] : 0,
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
            'select_employee',
            [
                'label' => BackendStrings::getWordPressStrings()['select_employee'],
                'type' => Controls_Manager::SELECT,
                'options' => $controls_data['employees'],
                'condition' => ['preselect' => 'yes'],
                'default' => '0',
            ]
        );

        $this->add_control(
            'select_location',
            [
                'label' => BackendStrings::getWordPressStrings()['select_location'],
                'type' => Controls_Manager::SELECT,
                'options' => $controls_data['locations'],
                'condition' => ['preselect' => 'yes'],
                'default' => '0',
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

        $this->end_controls_section();
    }

    protected function render()
    {
        $settings = $this->get_settings_for_display();

        if ($settings['select_catalog'] === 'show_service' || $settings['select_catalog'] === 'show_package') {
            $this->remove_control('select_show');
        }

        $trigger = $settings['load_manually'] !== '' ? ' trigger=' . $settings['load_manually'] : '';

        $show = '';

        if ($settings['select_catalog'] === 'show_catalog') {
            $category_service = '';

            $show = empty($settings['select_show']) ? '' : ' show=' . $settings['select_show'];
        } elseif ($settings['select_catalog'] === 'show_category') {
            $category_service = ' category=' . $settings['select_category'];

            $show = empty($settings['select_show']) ? '' : ' show=' . $settings['select_show'];
        } elseif ($settings['select_catalog'] === 'show_service') {
            $category_service = ' service=' . $settings['select_service'];
        } elseif ($settings['select_catalog'] === 'show_package') {
            $category_service = ' package=' . $settings['select_package'];
        } else {
            $category_service = '';
        }

        if ($settings['preselect']) {
            $employee = $settings['select_employee'] === '0' ? '' : ' employee=' . $settings['select_employee'];
            $location = $settings['select_location'] === '0' ? '' : ' location=' . $settings['select_location'];
        } else {
            $employee = '';
            $location = '';
            $trigger  = '';
        }
        echo esc_html('[ameliacatalog' . $show . $trigger . $category_service . $employee . $location . ']');
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

        $elementorData['employees']    = [];
        $elementorData['employees'][0] = BackendStrings::getWordPressStrings()['show_all_employees'];

        foreach ($data['employees'] as $provider) {
            $elementorData['employees'][$provider['id']] = $provider['firstName'] . $provider['lastName'] . ' (id: ' . $provider['id'] . ')';
        }

        $elementorData['locations']    = [];
        $elementorData['locations'][0] = BackendStrings::getWordPressStrings()['show_all_locations'];

        foreach ($data['locations'] as $location) {
            $elementorData['locations'][$location['id']] = $location['name'] . ' (id: ' . $location['id'] . ')';
        }

        $elementorData['show'] = $data['packages'] ? [
            '' => BackendStrings::getWordPressStrings()['show_all'],
            'services' => BackendStrings::getCommonStrings()['services'],
            'packages' => BackendStrings::getCommonStrings()['packages']
        ] : [];

        return $elementorData;
    }
}
