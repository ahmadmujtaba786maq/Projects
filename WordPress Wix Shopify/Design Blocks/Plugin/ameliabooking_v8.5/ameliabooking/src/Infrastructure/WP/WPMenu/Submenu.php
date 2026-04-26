<?php

namespace AmeliaBooking\Infrastructure\WP\WPMenu;

/**
 * Class Submenu
 *
 * @package AmeliaBooking\Infrastructure\WPMenu
 */
class Submenu
{
    /** @var SubmenuPageHandler $submenuHandler */
    private $submenuHandler;

    /** @var  array $menu */
    private $menu;

    /**
     * Submenu constructor.
     *
     * @param SubmenuPageHandler $submenuHandler
     * @param array              $menu
     */
    public function __construct($submenuHandler, $menu)
    {
        $this->submenuHandler = $submenuHandler;

        $this->menu = $menu;
    }

    /**
     * Add options in WP menu
     */
    public function addOptionsPages()
    {
        add_menu_page(
            'Amelia Booking',
            'Amelia',
            'amelia_read_menu',
            'amelia',
            '',
            AMELIA_URL . 'public/img/amelia-logo-admin-icon.svg'
        );

        $isLite = false;

        foreach ($this->menu as $menu) {
            if ($menu['menuSlug'] === 'wpamelia-lite-vs-premium') {
                $isLite = true;
            }
        }

        foreach ($this->menu as $menu) {
            $this->handleMenuItem($menu, $isLite);
        }

        remove_submenu_page('amelia', 'amelia');
    }

    /**
     * @param array $menu
     * @param bool  $isLite
     */
    public function handleMenuItem($menu, $isLite)
    {
        if ($menu['menuSlug'] === 'wpamelia-whats-new') {
            $menu['menuTitle'] = (!$isLite ? '<span style="color: #FF8C00">' : '') . $menu['menuTitle'] . (!$isLite ? '</span>' : '');
        }

        if ($menu['menuSlug'] === 'wpamelia-lite-vs-premium') {
            $menu['menuTitle'] = '<span style="color: #ff8c00;font-weight: 500;display: inline-block;margin-top: 2px;">'
                . $menu['menuTitle'] . '</span>
                <span class="dashicons dashicons-star-filled" style="color: #ff8c00;margin-left: 5px;"></span>';
        }

        if ($menu['menuSlug'] === 'wpamelia-locations') {
            $menu['menuTitle'] = ($isLite ? '<span style="display: inline-block;margin-top: 2px;color:inherit;">' : '')
                . $menu['menuTitle'] . ($isLite ? '</span><span class="dashicons dashicons-star-filled" style="color: #ff8c00;margin-left: 5px;"></span>' : '');
        }

        if ($menu['menuSlug'] === 'wpamelia-cf') {
            $menu['menuTitle'] = ($isLite ? '<span style="display: inline-block;margin-top: 2px;color:inherit;">' : '')
                . $menu['menuTitle'] . ($isLite ? '</span><span class="dashicons dashicons-star-filled" style="color: #ff8c00;margin-left: 5px;"></span>' : '');
        }

        $this->addSubmenuPage(
            $menu['parentSlug'],
            $menu['pageTitle'],
            $menu['menuTitle'],
            $menu['capability'],
            $menu['menuSlug'],
            function () use ($menu) {
                $this->submenuHandler->render($menu['menuSlug']);
            }
        );
    }

    /**
     * @noinspection MoreThanThreeArgumentsInspection
     *
     * @param        $parentSlug
     * @param        $pageTitle
     * @param        $menuTitle
     * @param        $capability
     * @param        $menuSlug
     * @param string $function
     */
    private function addSubmenuPage($parentSlug, $pageTitle, $menuTitle, $capability, $menuSlug, $function = '')
    {
        add_submenu_page(
            $parentSlug,
            $pageTitle,
            $menuTitle,
            $capability,
            $menuSlug,
            $function
        );
    }
}
