<?php

/**
 * @copyright © TMS-Plugins. All rights reserved.
 * @licence   See LICENCE.md for license details.
 */

namespace Elementor;

use AmeliaBooking\Infrastructure\WP\GutenbergBlock\GutenbergBlock;
use AmeliaBooking\Infrastructure\WP\Translations\BackendStrings;

/**
 * Class AmeliaSearchElementorWidget
 *
 * @package AmeliaBooking\Infrastructure\WP\Elementor
 */
class AmeliaSearchElementorWidget extends Widget_Base
{
    public function get_name()
    {
        return 'ameliasearch';
    }

    protected function register_controls()
    {
        $controls_data = self::amelia_elementor_get_data();

        $this->start_controls_section(
            'amelia_search_section',
            [
                'label' => '<div class="amelia-elementor-content-outdated"><p class="amelia-elementor-content-title">'
                    . BackendStrings::getWordPressStrings()['search_gutenberg_block']['title']
                    . '</p><br><p class="amelia-elementor-content-p">'
                    . BackendStrings::getWordPressStrings()['search_gutenberg_block']['description']
                    . '</p><br><p class="amelia-elementor-content-p amelia-elementor-content-p-outdated">'
                    . BackendStrings::getWordPressStrings()['outdated_booking_gutenberg_block']
                    . '</p>',
            ]
        );
        $this->add_control(
            'search-preselect-today',
            [
                'label' => BackendStrings::getWordPressStrings()['search_date'],
                'type' => Controls_Manager::SWITCHER,
                'default' => false,
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

        if ($controls_data['show']) {
            $this->add_control(
                'select_show',
                [
                    'label' => BackendStrings::getWordPressStrings()['show_all'],
                    'type' => Controls_Manager::SELECT,
                    'options' => $controls_data['show'],
                    'default' => '',
                ]
            );
        }

        $this->end_controls_section();
    }

    protected function render()
    {
        $settings        = $this->get_settings_for_display();
        $trigger         = $settings['load_manually'] !== '' ? ' trigger=' . $settings['load_manually'] : '';
        $preselect_today = $settings['search-preselect-today'] ? '  today=1' : '';

        $show = empty($settings['select_show']) ? '' : ' show=' . $settings['select_show'];

        echo esc_html('[ameliasearch' . $trigger . $show . $preselect_today . ']');
    }

    public static function amelia_elementor_get_data()
    {
        $data          = GutenbergBlock::getEntitiesData()['data'];
        $elementorData = [];

        $elementorData['show'] = $data['packages'] ? [
            '' => BackendStrings::getWordPressStrings()['show_all'],
            'services' => BackendStrings::getCommonStrings()['services'],
            'packages' => BackendStrings::getCommonStrings()['packages']
        ] : [];

        return $elementorData;
    }
}
