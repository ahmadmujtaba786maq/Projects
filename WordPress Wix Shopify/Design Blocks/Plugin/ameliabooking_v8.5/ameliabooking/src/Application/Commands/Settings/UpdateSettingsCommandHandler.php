<?php

namespace AmeliaBooking\Application\Commands\Settings;

use AmeliaBooking\Application\Commands\CommandHandler;
use AmeliaBooking\Application\Commands\CommandResult;
use AmeliaBooking\Application\Common\Exceptions\AccessDeniedException;
use AmeliaBooking\Application\Services\Location\AbstractCurrentLocation;
use AmeliaBooking\Application\Services\Stash\StashApplicationService;
use AmeliaBooking\Domain\Collection\Collection;
use AmeliaBooking\Domain\Common\Exceptions\ForbiddenFileUploadException;
use AmeliaBooking\Domain\Entity\Entities;
use AmeliaBooking\Domain\Entity\User\AbstractUser;
use AmeliaBooking\Domain\Services\Api\BasicApiService;
use AmeliaBooking\Domain\Services\Settings\SettingsService;
use AmeliaBooking\Infrastructure\Services\Apple\AbstractAppleCalendarService;
use AmeliaBooking\Infrastructure\Services\Frontend\LessParserService;
use AmeliaBooking\Infrastructure\Services\LessonSpace\AbstractLessonSpaceService;
use AmeliaBooking\Infrastructure\WP\Integrations\WooCommerce\WooCommerceService;
use Exception;
use Interop\Container\Exception\ContainerException;
use Less_Exception_Parser;
use Slim\Exception\ContainerValueNotFoundException;

/**
 * Class UpdateSettingsCommandHandler
 *
 * @package AmeliaBooking\Application\Commands\Settings
 */
class UpdateSettingsCommandHandler extends CommandHandler
{
    /**
     * @param UpdateSettingsCommand $command
     *
     * @return CommandResult
     * @throws AccessDeniedException
     * @throws Less_Exception_Parser
     * @throws ContainerValueNotFoundException
     * @throws ContainerException
     * @throws Exception
     */
    public function handle(UpdateSettingsCommand $command)
    {
        $result = new CommandResult();

        if (!$this->getContainer()->getPermissionsService()->currentUserCanWrite(Entities::SETTINGS)) {
            /** @var AbstractUser $loggedInUser */
            $loggedInUser = $this->container->get('logged.in.user');

            if (
                !$loggedInUser || !(
                    $loggedInUser->getType() === AbstractUser::USER_ROLE_ADMIN ||
                    $loggedInUser->getType() === AbstractUser::USER_ROLE_MANAGER
                )
            ) {
                throw new AccessDeniedException('You are not allowed to write settings.');
            }
        }

        /** @var SettingsService $settingsService */
        $settingsService = $this->getContainer()->get('domain.settings.service');

        /** @var AbstractCurrentLocation $locationService */
        $locationService = $this->getContainer()->get('application.currentLocation.service');

        /** @var LessParserService $lessParserService */
        $lessParserService = $this->getContainer()->get('infrastructure.frontend.lessParser.service');

        $settingsFields = $command->getFields();

        if ($command->getField('customization')) {
            $customizationData = $command->getField('customization');

            $globalColors = $customizationData['globalColors'];

            if (isset($settingsFields['customization']['forms']) && $settingsFields['customization']['forms']) {
                $settingsService->fixCustomization($settingsFields['customization']);
            }

            //Sbs - Step by step
            $useGlobalSbs = $customizationData['useGlobalColors']['stepByStepForm'];
            $colorSbsSsf  = $customizationData['forms']['stepByStepForm']['selectServiceForm']['globalSettings'];
            $colorSbsCf   = $customizationData['forms']['stepByStepForm']['calendarDateTimeForm']['globalSettings'];
            $colorSbsRsf  = $customizationData['forms']['stepByStepForm']['recurringSetupForm']['globalSettings'];
            $colorSbsRdf  = $customizationData['forms']['stepByStepForm']['recurringDatesForm']['globalSettings'];
            $colorSbsCaf  = $customizationData['forms']['stepByStepForm']['confirmBookingForm']['appointment']['globalSettings'];
            $colorSbsCoa  = $customizationData['forms']['stepByStepForm']['congratulationsForm']['appointment']['globalSettings'];
            $colorSbsSpf  = $customizationData['forms']['stepByStepForm']['selectPackageForm']['globalSettings'];
            $colorSbsPif  = $customizationData['forms']['stepByStepForm']['packageInfoForm']['globalSettings'];
            $colorSbsPsf  = $customizationData['forms']['stepByStepForm']['packageSetupForm']['globalSettings'];
            $colorSbsPlf  = $customizationData['forms']['stepByStepForm']['packageListForm']['globalSettings'];
            $colorSbsCpf  = $customizationData['forms']['stepByStepForm']['confirmBookingForm']['package']['globalSettings'];
            $colorSbsCop  = $customizationData['forms']['stepByStepForm']['congratulationsForm']['package']['globalSettings'];

            // Cf - Catalog form
            $useGlobalCf = $customizationData['useGlobalColors']['catalogForm'];
            $colorCfSsf  = $customizationData['forms']['catalogForm']['selectServiceForm']['globalSettings'];
            $colorCfCf   = $customizationData['forms']['catalogForm']['calendarDateTimeForm']['globalSettings'];
            $colorCfRsf  = $customizationData['forms']['catalogForm']['recurringSetupForm']['globalSettings'];
            $colorCfRdf  = $customizationData['forms']['catalogForm']['recurringDatesForm']['globalSettings'];
            $colorCfCaf  = $customizationData['forms']['catalogForm']['confirmBookingForm']['appointment']['globalSettings'];
            $colorCfCoa  = $customizationData['forms']['catalogForm']['congratulationsForm']['appointment']['globalSettings'];
            $colorCfPsf  = $customizationData['forms']['catalogForm']['packageSetupForm']['globalSettings'];
            $colorCfPlf  = $customizationData['forms']['catalogForm']['packageListForm']['globalSettings'];
            $colorCfCpf  = $customizationData['forms']['catalogForm']['confirmBookingForm']['package']['globalSettings'];
            $colorCfCop  = $customizationData['forms']['catalogForm']['congratulationsForm']['package']['globalSettings'];

            // Elf - Event list form
            $useGlobalElf = $customizationData['useGlobalColors']['eventListForm'];
            $colorElf     = $customizationData['forms']['eventListForm']['globalSettings'] ;

            // Ecf - Event calendar form
            $useGlobalEcf = $customizationData['useGlobalColors']['eventCalendarForm'];
            $colorEcfCef  = $customizationData['forms']['eventCalendarForm']['confirmBookingForm']['event']['globalSettings'];
            $colorEcfCoe  = $customizationData['forms']['eventCalendarForm']['congratulationsForm']['event']['globalSettings'];

            $hasCustomFont = !empty($customizationData['customFontSelected']) &&
                $customizationData['customFontSelected'] === 'selected';

            $hash = $lessParserService->compileAndSave(
                [
                    'color-accent'                => $globalColors['primaryColor'],
                    'color-white'                 => $globalColors['textColorOnBackground'],
                    'color-text-prime'            => $globalColors['formTextColor'],
                    'color-text-second'           => $globalColors['formTextColor'],
                    'color-bgr'                   => $globalColors['formBackgroundColor'],
                    'color-gradient1'             => $globalColors['formGradientColor1'],
                    'color-gradient2'             => $globalColors['formGradientColor2'],
                    'color-dropdown'              => $globalColors['formDropdownColor'],
                    'color-dropdown-text'         => $globalColors['formDropdownTextColor'],
                    'color-input'                 => $globalColors['formInputColor'],
                    'color-input-text'            => $globalColors['formInputTextColor'],
                    'font'                        => !empty($customizationData['font']) ?
                        $customizationData['font'] : '',
                    'custom-font-selected'        => $hasCustomFont ? 'selected' : 'unselected',
                    'font-url'                    => !empty($customizationData['fontUrl']) ?
                        $customizationData['fontUrl'] : '',
                    // step by step
                    'sbs-ssf-bgr-color'           => $useGlobalSbs ? $globalColors['formBackgroundColor'] : $colorSbsSsf['formBackgroundColor'],
                    'sbs-ssf-text-color'          => $useGlobalSbs ? $globalColors['formTextColor'] : $colorSbsSsf['formTextColor'],
                    'sbs-ssf-input-color'         => $useGlobalSbs ? $globalColors['formInputColor'] : $colorSbsSsf['formInputColor'],
                    'sbs-ssf-input-text-color'    => $useGlobalSbs ? $globalColors['formInputTextColor'] : $colorSbsSsf['formInputTextColor'],
                    'sbs-ssf-dropdown-color'      => $useGlobalSbs ? $globalColors['formDropdownColor'] : $colorSbsSsf['formDropdownColor'],
                    'sbs-ssf-dropdown-text-color' => $useGlobalSbs ? $globalColors['formDropdownTextColor'] : $colorSbsSsf['formDropdownTextColor'],
                    'sbs-cf-gradient1'            => $useGlobalSbs ? $globalColors['formGradientColor1'] : $colorSbsCf['formGradientColor1'],
                    'sbs-cf-gradient2'            => $useGlobalSbs ? $globalColors['formGradientColor2'] : $colorSbsCf['formGradientColor2'],
                    'sbs-cf-gradient-angle'       => $useGlobalSbs ? $globalColors['formGradientAngle'] . 'deg' : $colorSbsCf['formGradientAngle'] . 'deg',
                    'sbs-cf-text-color'           => $useGlobalSbs ? $globalColors['textColorOnBackground'] : $colorSbsCf['formTextColor'],
                    'sbs-rsf-gradient1'           => $useGlobalSbs ? $globalColors['formGradientColor1'] : $colorSbsRsf['formGradientColor1'],
                    'sbs-rsf-gradient2'           => $useGlobalSbs ? $globalColors['formGradientColor2'] : $colorSbsRsf['formGradientColor2'],
                    'sbs-rsf-gradient-angle'      => $useGlobalSbs ? $globalColors['formGradientAngle'] . 'deg' : $colorSbsRsf['formGradientAngle'] . 'deg',
                    'sbs-rsf-text-color'          => $useGlobalSbs ? $globalColors['textColorOnBackground'] : $colorSbsRsf['formTextColor'],
                    'sbs-rsf-input-color'         => $useGlobalSbs ? $globalColors['formInputColor'] : $colorSbsRsf['formInputColor'],
                    'sbs-rsf-input-text-color'    => $useGlobalSbs ? $globalColors['formInputTextColor'] : $colorSbsRsf['formInputTextColor'],
                    'sbs-rsf-dropdown-color'      => $useGlobalSbs ? $globalColors['formDropdownColor'] : $colorSbsRsf['formDropdownColor'],
                    'sbs-rsf-dropdown-text-color' => $useGlobalSbs ? $globalColors['formDropdownTextColor'] : $colorSbsRsf['formDropdownTextColor'],
                    'sbs-rdf-bgr-color'           => $useGlobalSbs ? $globalColors['formBackgroundColor'] : $colorSbsRdf['formBackgroundColor'],
                    'sbs-rdf-text-color'          => $useGlobalSbs ? $globalColors['formTextColor'] : $colorSbsRdf['formTextColor'],
                    'sbs-rdf-input-color'         => $useGlobalSbs ? $globalColors['formInputColor'] : $colorSbsRdf['formInputColor'],
                    'sbs-rdf-input-text-color'    => $useGlobalSbs ? $globalColors['formInputTextColor'] : $colorSbsRdf['formInputTextColor'],
                    'sbs-rdf-dropdown-color'      => $useGlobalSbs ? $globalColors['formDropdownColor'] : $colorSbsRdf['formDropdownColor'],
                    'sbs-rdf-dropdown-text-color' => $useGlobalSbs ? $globalColors['formDropdownTextColor'] : $colorSbsRdf['formDropdownTextColor'],
                    'sbs-caf-bgr-color'           => $useGlobalSbs ? $globalColors['formBackgroundColor'] : $colorSbsCaf['formBackgroundColor'],
                    'sbs-caf-text-color'          => $useGlobalSbs ? $globalColors['formTextColor'] : $colorSbsCaf['formTextColor'],
                    'sbs-caf-input-color'         => $useGlobalSbs ? $globalColors['formInputColor'] : $colorSbsCaf['formInputColor'],
                    'sbs-caf-input-text-color'    => $useGlobalSbs ? $globalColors['formInputTextColor'] : $colorSbsCaf['formInputTextColor'],
                    'sbs-caf-dropdown-color'      => $useGlobalSbs ? $globalColors['formDropdownColor'] : $colorSbsCaf['formDropdownColor'],
                    'sbs-caf-dropdown-text-color' => $useGlobalSbs ? $globalColors['formDropdownTextColor'] : $colorSbsCaf['formDropdownTextColor'],
                    'sbs-spf-bgr-color'           => $useGlobalSbs ? $globalColors['formBackgroundColor'] : $colorSbsSpf['formBackgroundColor'],
                    'sbs-spf-text-color'          => $useGlobalSbs ? $globalColors['formTextColor'] : $colorSbsSpf['formTextColor'],
                    'sbs-spf-input-color'         => $useGlobalSbs ? $globalColors['formInputColor'] : $colorSbsSpf['formInputColor'],
                    'sbs-spf-input-text-color'    => $useGlobalSbs ? $globalColors['formInputTextColor'] : $colorSbsSpf['formInputTextColor'],
                    'sbs-spf-dropdown-color'      => $useGlobalSbs ? $globalColors['formDropdownColor'] : $colorSbsSpf['formDropdownColor'],
                    'sbs-spf-dropdown-text-color' => $useGlobalSbs ? $globalColors['formDropdownTextColor'] : $colorSbsSpf['formDropdownTextColor'],
                    'sbs-pif-bgr-color'           => $useGlobalSbs ? $globalColors['formBackgroundColor'] : $colorSbsPif['formBackgroundColor'],
                    'sbs-pif-text-color'          => $useGlobalSbs ? $globalColors['formTextColor'] : $colorSbsPif['formTextColor'],
                    'sbs-psf-gradient1'           => $useGlobalSbs ? $globalColors['formGradientColor1'] : $colorSbsPsf['formGradientColor1'],
                    'sbs-psf-gradient2'           => $useGlobalSbs ? $globalColors['formGradientColor2'] : $colorSbsPsf['formGradientColor2'],
                    'sbs-psf-gradient-angle'      => $useGlobalSbs ? $globalColors['formGradientAngle'] . 'deg' : $colorSbsPsf['formGradientAngle'] . 'deg',
                    'sbs-psf-text-color'          => $useGlobalSbs ? $globalColors['textColorOnBackground'] : $colorSbsPsf['formTextColor'],
                    'sbs-psf-input-color'         => $useGlobalSbs ? $globalColors['formInputColor'] : $colorSbsPsf['formInputColor'],
                    'sbs-psf-input-text-color'    => $useGlobalSbs ? $globalColors['formInputTextColor'] : $colorSbsPsf['formInputTextColor'],
                    'sbs-psf-dropdown-color'      => $useGlobalSbs ? $globalColors['formDropdownColor'] : $colorSbsPsf['formDropdownColor'],
                    'sbs-psf-dropdown-text-color' => $useGlobalSbs ? $globalColors['formDropdownTextColor'] : $colorSbsPsf['formDropdownTextColor'],
                    'sbs-plf-bgr-color'           => $useGlobalSbs ? $globalColors['formBackgroundColor'] : $colorSbsPlf['formBackgroundColor'],
                    'sbs-plf-text-color'          => $useGlobalSbs ? $globalColors['formTextColor'] : $colorSbsPlf['formTextColor'],
                    'sbs-cpf-bgr-color'           => $useGlobalSbs ? $globalColors['formBackgroundColor'] : $colorSbsCpf['formBackgroundColor'],
                    'sbs-cpf-text-color'          => $useGlobalSbs ? $globalColors['formTextColor'] : $colorSbsCpf['formTextColor'],
                    'sbs-cpf-input-color'         => $useGlobalSbs ? $globalColors['formInputColor'] : $colorSbsCpf['formInputColor'],
                    'sbs-cpf-input-text-color'    => $useGlobalSbs ? $globalColors['formInputTextColor'] : $colorSbsCpf['formInputTextColor'],
                    'sbs-cpf-dropdown-color'      => $useGlobalSbs ? $globalColors['formDropdownColor'] : $colorSbsCpf['formDropdownColor'],
                    'sbs-cpf-dropdown-text-color' => $useGlobalSbs ? $globalColors['formDropdownTextColor'] : $colorSbsCpf['formDropdownTextColor'],
                    'sbs-coa-bgr-color'           => $useGlobalSbs ? $globalColors['formBackgroundColor'] : $colorSbsCoa['formBackgroundColor'],
                    'sbs-coa-text-color'          => $useGlobalSbs ? $globalColors['formTextColor'] : $colorSbsCoa['formTextColor'],
                    'sbs-coa-input-color'         => $useGlobalSbs ? $globalColors['formInputColor'] : $colorSbsCoa['formInputColor'],
                    'sbs-coa-input-text-color'    => $useGlobalSbs ? $globalColors['formInputTextColor'] : $colorSbsCoa['formInputTextColor'],
                    'sbs-coa-dropdown-color'      => $useGlobalSbs ? $globalColors['formDropdownColor'] : $colorSbsCoa['formDropdownColor'],
                    'sbs-coa-dropdown-text-color' => $useGlobalSbs ? $globalColors['formDropdownTextColor'] : $colorSbsCoa['formDropdownTextColor'],
                    'sbs-cop-bgr-color'           => $useGlobalSbs ? $globalColors['formBackgroundColor'] : $colorSbsCop['formBackgroundColor'],
                    'sbs-cop-text-color'          => $useGlobalSbs ? $globalColors['formTextColor'] : $colorSbsCop['formTextColor'],
                    'sbs-cop-input-color'         => $useGlobalSbs ? $globalColors['formInputColor'] : $colorSbsCop['formInputColor'],
                    'sbs-cop-input-text-color'    => $useGlobalSbs ? $globalColors['formInputTextColor'] : $colorSbsCop['formInputTextColor'],
                    'sbs-cop-dropdown-color'      => $useGlobalSbs ? $globalColors['formDropdownColor'] : $colorSbsCop['formDropdownColor'],
                    'sbs-cop-dropdown-text-color' => $useGlobalSbs ? $globalColors['formDropdownTextColor'] : $colorSbsCop['formDropdownTextColor'],
                    // catalog
                    'cf-ssf-bgr-color'            => $useGlobalCf ? $globalColors['formBackgroundColor'] : $colorCfSsf['formBackgroundColor'],
                    'cf-ssf-text-color'           => $useGlobalCf ? $globalColors['formTextColor'] : $colorCfSsf['formTextColor'],
                    'cf-ssf-input-color'          => $useGlobalCf ? $globalColors['formInputColor'] : $colorCfSsf['formInputColor'],
                    'cf-ssf-input-text-color'     => $useGlobalCf ? $globalColors['formInputTextColor'] : $colorCfSsf['formInputTextColor'],
                    'cf-ssf-dropdown-color'       => $useGlobalCf ? $globalColors['formDropdownColor'] : $colorCfSsf['formDropdownColor'],
                    'cf-ssf-dropdown-text-color'  => $useGlobalCf ? $globalColors['formDropdownTextColor'] : $colorCfSsf['formDropdownTextColor'],
                    'cf-cf-gradient1'             => $useGlobalCf ? $globalColors['formGradientColor1'] : $colorCfCf['formGradientColor1'],
                    'cf-cf-gradient2'             => $useGlobalCf ? $globalColors['formGradientColor2'] : $colorCfCf['formGradientColor2'],
                    'cf-cf-gradient-angle'        => $useGlobalCf ? $globalColors['formGradientAngle'] . 'deg' : $colorCfCf['formGradientAngle'] . 'deg',
                    'cf-cf-text-color'            => $useGlobalCf ? $globalColors['textColorOnBackground'] : $colorCfCf['formTextColor'],
                    'cf-rsf-gradient1'            => $useGlobalCf ? $globalColors['formGradientColor1'] : $colorCfRsf['formGradientColor1'],
                    'cf-rsf-gradient2'            => $useGlobalCf ? $globalColors['formGradientColor2'] : $colorCfRsf['formGradientColor2'],
                    'cf-rsf-gradient-angle'       => $useGlobalCf ? $globalColors['formGradientAngle'] . 'deg' : $colorCfRsf['formGradientAngle'] . 'deg',
                    'cf-rsf-text-color'           => $useGlobalCf ? $globalColors['textColorOnBackground'] : $colorCfRsf['formTextColor'],
                    'cf-rsf-input-color'          => $useGlobalCf ? $globalColors['formInputColor'] : $colorCfRsf['formInputColor'],
                    'cf-rsf-input-text-color'     => $useGlobalCf ? $globalColors['formInputTextColor'] : $colorCfRsf['formInputTextColor'],
                    'cf-rsf-dropdown-color'       => $useGlobalCf ? $globalColors['formDropdownColor'] : $colorCfRsf['formDropdownColor'],
                    'cf-rsf-dropdown-text-color'  => $useGlobalCf ? $globalColors['formDropdownTextColor'] : $colorCfRsf['formDropdownTextColor'],
                    'cf-rdf-bgr-color'            => $useGlobalCf ? $globalColors['formBackgroundColor'] : $colorCfRdf['formBackgroundColor'],
                    'cf-rdf-text-color'           => $useGlobalCf ? $globalColors['formTextColor'] : $colorCfRdf['formTextColor'],
                    'cf-rdf-input-color'          => $useGlobalCf ? $globalColors['formInputColor'] : $colorCfRdf['formInputColor'],
                    'cf-rdf-input-text-color'     => $useGlobalCf ? $globalColors['formInputTextColor'] : $colorCfRdf['formInputTextColor'],
                    'cf-rdf-dropdown-color'       => $useGlobalCf ? $globalColors['formDropdownColor'] : $colorCfRdf['formDropdownColor'],
                    'cf-rdf-dropdown-text-color'  => $useGlobalCf ? $globalColors['formDropdownTextColor'] : $colorCfRdf['formDropdownTextColor'],
                    'cf-caf-bgr-color'            => $useGlobalCf ? $globalColors['formBackgroundColor'] : $colorCfCaf['formBackgroundColor'],
                    'cf-caf-text-color'           => $useGlobalCf ? $globalColors['formTextColor'] : $colorCfCaf['formTextColor'],
                    'cf-caf-input-color'          => $useGlobalCf ? $globalColors['formInputColor'] : $colorCfCaf['formInputColor'],
                    'cf-caf-input-text-color'     => $useGlobalCf ? $globalColors['formInputTextColor'] : $colorCfCaf['formInputTextColor'],
                    'cf-caf-dropdown-color'       => $useGlobalCf ? $globalColors['formDropdownColor'] : $colorCfCaf['formDropdownColor'],
                    'cf-caf-dropdown-text-color'  => $useGlobalCf ? $globalColors['formDropdownTextColor'] : $colorCfCaf['formDropdownTextColor'],
                    'cf-psf-gradient1'            => $useGlobalCf ? $globalColors['formGradientColor1'] : $colorCfPsf['formGradientColor1'],
                    'cf-psf-gradient2'            => $useGlobalCf ? $globalColors['formGradientColor2'] : $colorCfPsf['formGradientColor2'],
                    'cf-psf-gradient-angle'       => $useGlobalCf ? $globalColors['formGradientAngle'] . 'deg' : $colorCfPsf['formGradientAngle'] . 'deg',
                    'cf-psf-text-color'           => $useGlobalCf ? $globalColors['textColorOnBackground'] : $colorCfPsf['formTextColor'],
                    'cf-psf-input-color'          => $useGlobalCf ? $globalColors['formInputColor'] : $colorCfPsf['formInputColor'],
                    'cf-psf-input-text-color'     => $useGlobalCf ? $globalColors['formInputTextColor'] : $colorCfPsf['formInputTextColor'],
                    'cf-psf-dropdown-color'       => $useGlobalCf ? $globalColors['formDropdownColor'] : $colorCfPsf['formDropdownColor'],
                    'cf-psf-dropdown-text-color'  => $useGlobalCf ? $globalColors['formDropdownTextColor'] : $colorCfPsf['formDropdownTextColor'],
                    'cf-plf-bgr-color'            => $useGlobalCf ? $globalColors['formBackgroundColor'] : $colorCfPlf['formBackgroundColor'],
                    'cf-plf-text-color'           => $useGlobalCf ? $globalColors['formTextColor'] : $colorCfPlf['formTextColor'],
                    'cf-cpf-bgr-color'            => $useGlobalCf ? $globalColors['formBackgroundColor'] : $colorCfCpf['formBackgroundColor'],
                    'cf-cpf-text-color'           => $useGlobalCf ? $globalColors['formTextColor'] : $colorCfCpf['formTextColor'],
                    'cf-cpf-input-color'          => $useGlobalCf ? $globalColors['formInputColor'] : $colorCfCpf['formInputColor'],
                    'cf-cpf-input-text-color'     => $useGlobalCf ? $globalColors['formInputTextColor'] : $colorCfCpf['formInputTextColor'],
                    'cf-cpf-dropdown-color'       => $useGlobalCf ? $globalColors['formDropdownColor'] : $colorCfCpf['formDropdownColor'],
                    'cf-cpf-dropdown-text-color'  => $useGlobalCf ? $globalColors['formDropdownTextColor'] : $colorCfCpf['formDropdownTextColor'],
                    'cf-coa-bgr-color'            => $useGlobalCf ? $globalColors['formBackgroundColor'] : $colorCfCoa['formBackgroundColor'],
                    'cf-coa-text-color'           => $useGlobalCf ? $globalColors['formTextColor'] : $colorCfCoa['formTextColor'],
                    'cf-coa-input-color'          => $useGlobalCf ? $globalColors['formInputColor'] : $colorCfCoa['formInputColor'],
                    'cf-coa-input-text-color'     => $useGlobalCf ? $globalColors['formInputTextColor'] : $colorCfCoa['formInputTextColor'],
                    'cf-coa-dropdown-color'       => $useGlobalCf ? $globalColors['formDropdownColor'] : $colorCfCoa['formDropdownColor'],
                    'cf-coa-dropdown-text-color'  => $useGlobalCf ? $globalColors['formDropdownTextColor'] : $colorCfCoa['formDropdownTextColor'],
                    'cf-cop-bgr-color'            => $useGlobalCf ? $globalColors['formBackgroundColor'] : $colorCfCop['formBackgroundColor'],
                    'cf-cop-text-color'           => $useGlobalCf ? $globalColors['formTextColor'] : $colorCfCop['formTextColor'],
                    'cf-cop-input-color'          => $useGlobalCf ? $globalColors['formInputColor'] : $colorCfCop['formInputColor'],
                    'cf-cop-input-text-color'     => $useGlobalCf ? $globalColors['formInputTextColor'] : $colorCfCop['formInputTextColor'],
                    'cf-cop-dropdown-color'       => $useGlobalCf ? $globalColors['formDropdownColor'] : $colorCfCop['formDropdownColor'],
                    'cf-cop-dropdown-text-color'  => $useGlobalCf ? $globalColors['formDropdownTextColor'] : $colorCfCop['formDropdownTextColor'],
                    // event list
                    'elf-bgr-color'               => $useGlobalElf ? $globalColors['formBackgroundColor'] : $colorElf['formBackgroundColor'],
                    'elf-text-color'              => $useGlobalElf ? $globalColors['formTextColor'] : $colorElf['formTextColor'],
                    'elf-input-color'             => $useGlobalElf ? $globalColors['formInputColor'] : $colorElf['formInputColor'],
                    'elf-input-text-color'        => $useGlobalElf ? $globalColors['formInputTextColor'] : $colorElf['formInputTextColor'],
                    'elf-dropdown-color'          => $useGlobalElf ? $globalColors['formDropdownColor'] : $colorElf['formDropdownColor'],
                    'elf-dropdown-text-color'     => $useGlobalElf ? $globalColors['formDropdownTextColor'] : $colorElf['formDropdownTextColor'],
                    // event calendar
                    'ecf-cef-bgr-color'           => $useGlobalEcf ? $globalColors['formBackgroundColor'] : $colorEcfCef['formBackgroundColor'],
                    'ecf-cef-text-color'          => $useGlobalEcf ? $globalColors['formTextColor'] : $colorEcfCef['formTextColor'],
                    'ecf-cef-input-color'         => $useGlobalEcf ? $globalColors['formInputColor'] : $colorEcfCef['formInputColor'],
                    'ecf-cef-input-text-color'    => $useGlobalEcf ? $globalColors['formInputTextColor'] : $colorEcfCef['formInputTextColor'],
                    'ecf-cef-dropdown-color'      => $useGlobalEcf ? $globalColors['formDropdownColor'] : $colorEcfCef['formDropdownColor'],
                    'ecf-cef-dropdown-text-color' => $useGlobalEcf ? $globalColors['formDropdownTextColor'] : $colorEcfCef['formDropdownTextColor'],
                    'ecf-coe-bgr-color'           => $useGlobalEcf ? $globalColors['formBackgroundColor'] : $colorEcfCoe['formBackgroundColor'],
                    'ecf-coe-text-color'          => $useGlobalEcf ? $globalColors['formTextColor'] : $colorEcfCoe['formTextColor'],
                    'ecf-coe-input-color'         => $useGlobalEcf ? $globalColors['formInputColor'] : $colorEcfCoe['formInputColor'],
                    'ecf-coe-input-text-color'    => $useGlobalEcf ? $globalColors['formInputTextColor'] : $colorEcfCoe['formInputTextColor'],
                    'ecf-coe-dropdown-color'      => $useGlobalEcf ? $globalColors['formDropdownColor'] : $colorEcfCoe['formDropdownColor'],
                    'ecf-coe-dropdown-text-color' => $useGlobalEcf ? $globalColors['formDropdownTextColor'] : $colorEcfCoe['formDropdownTextColor'],
                ]
            );

            $settingsFields['customization']['hash'] = $hash;

            $settingsFields['customization']['useGenerated'] = isset($customizationData['useGenerated']) ?
                $customizationData['useGenerated'] : true;
        }

        if (
            WooCommerceService::isEnabled() &&
            $command->getField('payments') &&
            $command->getField('payments')['wc']['enabled']
        ) {
            $settingsFields['payments']['wc']['productId'] = WooCommerceService::getIdForExistingOrNewProduct(
                $settingsService->getCategorySettings('payments')['wc']['productId']
            );
        }


        if ($command->getField('general') && $command->getField('general')['customFieldsUploadsPath']) {
            $uploadPath = $command->getField('general')['customFieldsUploadsPath'];
            if ($uploadPath[0] !== '/') {
                throw new ForbiddenFileUploadException('Attachment upload path must be an absolute path, starting with a slash (/).');
            }

            !is_dir($uploadPath) && !mkdir($uploadPath, 0755, true);

            if (!is_writable($uploadPath) || !is_dir($uploadPath)) {
                throw new ForbiddenFileUploadException('Attachment upload path is not writable or does not exist.');
            }

            if (!file_exists("$uploadPath/index.html")) {
                file_put_contents("$uploadPath/index.html", '');
            }
        }

        if ($command->getField('sendAllCF') !== null) {
            $notificationsSettings = $settingsService->getCategorySettings('notifications');

            $settingsFields['notifications'] = $notificationsSettings;

            $settingsFields['notifications']['sendAllCF'] = $command->getField('sendAllCF');

            unset($settingsFields['sendAllCF']);
        }

        if ($command->getField('googleCalendar') !== null) {
            $googleSettings = $command->getField('googleCalendar');

            if (!$googleSettings['clientID'] || !$googleSettings['clientSecret']) {
                $googleSettings['calendarEnabled'] = false;
            }

            $settingsFields['googleCalendar'] = array_merge(
                $settingsService->getCategorySettings('googleCalendar'),
                $googleSettings
            );
        }

        if ($command->getField('outlookCalendar') !== null) {
            $outlookSettings = $command->getField('outlookCalendar');

            unset($outlookSettings['token']);

            if (!$outlookSettings['clientID'] || !$outlookSettings['clientSecret']) {
                $outlookSettings['calendarEnabled'] = false;
            }

            $settingsFields['outlookCalendar'] = array_merge(
                $settingsService->getCategorySettings('outlookCalendar'),
                $outlookSettings
            );
        }

        if ($command->getField('providerBadges') !== null) {
            $rolesSettings = $settingsService->getCategorySettings('roles');

            $settingsFields['roles'] = $rolesSettings;

            $settingsFields['roles']['providerBadges'] = $command->getField('providerBadges');

            unset($settingsFields['providerBadges']);
        }

        if (
            !$settingsService->getCategorySettings('activation')['stash'] &&
            !empty($settingsFields['activation']['stash'])
        ) {
            /** @var StashApplicationService $stashApplicationService */
            $stashApplicationService = $this->container->get('application.stash.service');

            $stashApplicationService->setStash();
        }

        if (
            isset($settingsFields['daysOff']) &&
            $settingsService->getCategorySettings('activation')['stash'] &&
            $settingsService->getCategorySettings('daysOff') !== $settingsFields['daysOff'] &&
            $command->getField('daysOff') !== null
        ) {
            /** @var StashApplicationService $stashApplicationService */
            $stashApplicationService = $this->container->get('application.stash.service');

            $stashApplicationService->setStash($settingsFields['daysOff']);
        }

        $settingsFields['activation'] = array_merge(
            $settingsService->getCategorySettings('activation'),
            isset($settingsFields['activation']['deleteTables']) ? [
                'deleteTables' => $settingsFields['activation']['deleteTables'] ? true : false
            ] : [],
            isset($settingsFields['activation']['envatoTokenEmail']) ? [
                'envatoTokenEmail' => $settingsFields['activation']['envatoTokenEmail']
            ] : [],
            isset($settingsFields['activation']['active']) ? [
                'active' => $settingsFields['activation']['active']
            ] : [],
            isset($settingsFields['activation']['stash']) ? [
                'stash' => $settingsFields['activation']['stash']
            ] : [],
            isset($settingsFields['activation']['showAmeliaPromoCustomizePopup']) ? [
                'showAmeliaPromoCustomizePopup' => $settingsFields['activation']['showAmeliaPromoCustomizePopup']
            ] : [],
            isset($settingsFields['activation']['showAmeliaSurvey']) ? [
                'showAmeliaSurvey' => $settingsFields['activation']['showAmeliaSurvey']
            ] : [],
            isset($settingsFields['activation']['customUrl']) ? [
                'customUrl' => $settingsFields['activation']['customUrl']
            ] : [],
            isset($settingsFields['activation']['v3AsyncLoading']) ? [
                'v3AsyncLoading' => $settingsFields['activation']['v3AsyncLoading']
            ] : [],
            isset($settingsFields['activation']['v3RelativePath']) ? [
                'v3RelativePath' => $settingsFields['activation']['v3RelativePath']
            ] : [],
            isset($settingsFields['activation']['enableThriveItems']) ? [
                'enableThriveItems' => $settingsFields['activation']['enableThriveItems']
            ] : [],
            isset($settingsFields['activation']['responseErrorAsConflict']) ? [
                'responseErrorAsConflict' => $settingsFields['activation']['responseErrorAsConflict']
            ] : [],
            isset($settingsFields['activation']['disableUrlParams']) ? [
                'disableUrlParams' => $settingsFields['activation']['disableUrlParams']
            ] : [],
            isset($settingsFields['activation']['hideUnavailableFeatures']) ? [
                'hideUnavailableFeatures' => $settingsFields['activation']['hideUnavailableFeatures']
            ] : [],
            isset($settingsFields['activation']['premiumBannerVisibility']) ? [
                'premiumBannerVisibility' => $settingsFields['activation']['premiumBannerVisibility']
            ] : [],
            isset($settingsFields['activation']['dismissibleBannerVisibility']) ? [
                'dismissibleBannerVisibility' => $settingsFields['activation']['dismissibleBannerVisibility']
            ] : []
        );

        if ($command->getField('usedLanguages') !== null) {
            $generalSettings = $settingsService->getCategorySettings('general');

            $settingsFields['general'] = $generalSettings;

            $settingsFields['general']['usedLanguages'] = $command->getField('usedLanguages');

            unset($settingsFields['usedLanguages']);
        }

        if ($command->getField('lessonSpace') !== null && $settingsFields['lessonSpace']['apiKey']) {
            if (!$settingsService->getCategorySettings('lessonSpace')['companyId']) {
                /** @var AbstractLessonSpaceService $lessonSpaceService */
                $lessonSpaceService = $this->container->get('infrastructure.lesson.space.service');

                $companyDetails = $lessonSpaceService->getCompanyId($settingsFields['lessonSpace']['apiKey']);

                $settingsFields['lessonSpace']['companyId'] = $companyDetails['id'];
            } else {
                $settingsFields['lessonSpace']['companyId'] = $settingsService->getCategorySettings('lessonSpace')['companyId'];
            }
        }

        if ($command->getField('payments') && !empty($command->getField('payments')['square'])) {
            $settingsFields['payments']['square']['accessToken'] = $settingsService->getCategorySettings('payments')['square']['accessToken'];
        }

        if (isset($settingsFields['apiKeys']) && isset($settingsFields['apiKeys']['apiKeys'])) {
            /** @var BasicApiService $apiService */
            $apiService = $this->getContainer()->get('domain.api.service');
            foreach ($settingsFields['apiKeys']['apiKeys'] as $index => $apiKey) {
                if (!empty($apiKey['isNew'])) {
                    $settingsFields['apiKeys']['apiKeys'][$index]['key'] = $apiService->createHash($settingsFields['apiKeys']['apiKeys'][$index]['key']);
                }
                unset($settingsFields['apiKeys']['apiKeys'][$index]['isNew']);
            }
        }

        if (!empty($command->getField('appleCalendar')['clientID']) || !empty($command->getField('appleCalendar')['clientSecret'])) {
            /** @var AbstractAppleCalendarService $appleCalendarService */
            $appleCalendarService = $this->container->get('infrastructure.apple.calendar.service');

            $appleId       = $command->getField('appleCalendar')['clientID'];
            $applePassword = $command->getField('appleCalendar')['clientSecret'];

            $credentials = $appleCalendarService->handleAppleCredentials($appleId, $applePassword);

            if (!$credentials) {
                $result->setDataInResponse(true);
                $result->setResult(CommandResult::RESULT_ERROR);
                $result->setMessage('Make sure you are using the correct iCloud email address and app-specific password.');

                return $result;
            }
        }

        if (
            $command->getField('appleCalendar') &&
            empty($command->getField('appleCalendar')['clientID']) &&
            empty($command->getField('appleCalendar')['clientSecret'])
        ) {
            $providerRepository = $this->container->get('domain.users.providers.repository');
            /** @var Collection $providers */
            $providers = $providerRepository->getAll();
            foreach ($providers->getItems() as $provider) {
                $providerRepository->updateFieldById($provider->getId()->getValue(), null, 'employeeAppleCalendar');
                $providerRepository->updateFieldById($provider->getId()->getValue(), null, 'appleCalendarId');
            }
        }

        if (!empty($command->getField('customizedData'))) {
            $passedCustomizedData = $command->getField('customizedData');
            $customizedData       = $settingsService->getCategorySettings('customizedData');

            foreach ($passedCustomizedData as $key => $value) {
                $customizedData[$key] = $value;
            }

            $settingsFields['customizedData'] = $customizedData;
        }

        $settingsFields = apply_filters('amelia_before_settings_updated_filter', $settingsFields);

        do_action('amelia_before_settings_updated', $settingsFields);

        $settingsService->setAllSettings($settingsFields);

        $settings = $settingsService->getAllSettingsCategorized();
        $settings['general']['phoneDefaultCountryCode'] = $settings['general']['phoneDefaultCountryCode'] === 'auto' ?
            $locationService->getCurrentLocationCountryIso($settings['general']['ipLocateApiKey']) : $settings['general']['phoneDefaultCountryCode'];

        do_action('amelia_after_settings_updated', $settingsFields);

        $result->setResult(CommandResult::RESULT_SUCCESS);
        $result->setMessage('Successfully updated settings.');
        $result->setData(
            [
                'settings' => $settings
            ]
        );

        return $result;
    }
}
