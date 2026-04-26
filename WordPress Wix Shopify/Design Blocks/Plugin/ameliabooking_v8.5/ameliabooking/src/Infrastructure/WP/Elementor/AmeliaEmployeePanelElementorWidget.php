<?php

/**
 * @copyright © TMS-Plugins. All rights reserved.
 * @licence   See LICENCE.md for license details.
 */

namespace Elementor;

use AmeliaBooking\Infrastructure\WP\Translations\BackendStrings;

/**
 * Class AmeliaEmployeePanelElementorWidget
 *
 * @package AmeliaBooking\Infrastructure\WP\Elementor
 */
class AmeliaEmployeePanelElementorWidget extends Widget_Base
{
    public function get_name()
    {
        return 'ameliaemployeepanel';
    }

    public function get_title()
    {
        return BackendStrings::getWordPressStrings()['employee_cabinet_gutenberg_block']['title'];
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
            'amelia_employee_panel_section',
            [
                'label' => '<div class="amelia-elementor-content"><p class="amelia-elementor-content-title">'
                    . BackendStrings::getWordPressStrings()['employee_cabinet_gutenberg_block']['title']
                    . '</p><br><p class="amelia-elementor-content-p">'
                    . BackendStrings::getWordPressStrings()['employee_cabinet_gutenberg_block']['description']
                    . '</p>',
            ]
        );
        $this->add_control(
            'appointments',
            [
                'label' => BackendStrings::getCommonStrings()['appointments'],
                'type' => Controls_Manager::SWITCHER,
                'default' => 'yes',
                'label_on' => BackendStrings::getCommonStrings()['yes'],
                'label_off' => BackendStrings::getCommonStrings()['no'],
            ]
        );
        $this->add_control(
            'events',
            [
                'label' => BackendStrings::getCommonStrings()['events'],
                'type' => Controls_Manager::SWITCHER,
                'default' => 'yes',
                'label_on' => BackendStrings::getCommonStrings()['yes'],
                'label_off' => BackendStrings::getCommonStrings()['no'],
            ]
        );
        $this->add_control(
            'profile',
            [
                'label' => BackendStrings::getCommonStrings()['profile'],
                'type' => Controls_Manager::SWITCHER,
                'default' => false,
                'label_on' => BackendStrings::getCommonStrings()['yes'],
                'label_off' => BackendStrings::getCommonStrings()['no'],
            ]
        );
        $this->add_control(
            'select_version',
            [
                'label' => BackendStrings::getWordPressStrings()['choose_panel_version'],
                'type' => Controls_Manager::SELECT,
                'options' => [
                    '1' => BackendStrings::getWordPressStrings()['panel_version_old'],
                    '2' => BackendStrings::getWordPressStrings()['panel_version_new']
                ],
                'default' => '2',
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
        $this->end_controls_section();
    }

    protected function render()
    {
        $settings     = $this->get_settings_for_display();
        $trigger      = $settings['load_manually'] !== '' ? ' trigger=' . $settings['load_manually'] : '';
        $appointments = $settings['appointments'] ? ' appointments=1' : '';
        $events       = $settings['events'] ? ' events=1' : '';
        $profile      = $settings['profile'] ? ' profile-hidden=1' : '';
        $version      = $settings['select_version'] ? ' version=' . $settings['select_version'] : '';
        if ($settings['appointments'] || $settings['events'] || !$settings['profile']) {
            echo esc_html('[ameliaemployeepanel' . $version . $trigger . $appointments . $events . $profile . ']');
        } else {
            echo esc_html(BackendStrings::getWordPressStrings()['notice_panel']);
        }
    }
}
