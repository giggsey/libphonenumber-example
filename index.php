<?php

require __DIR__ . '/vendor/autoload.php';

/* Load Twig */
$twig = new \Twig\Environment(new \Twig\Loader\FilesystemLoader(__DIR__ . '/templates/'));

$twig->addGlobal('baseurl', $_SERVER['SCRIPT_NAME']);
$twig->addGlobal('phpversion', phpversion());
$twig->addGlobal('version', \Composer\InstalledVersions::getVersion('giggsey/libphonenumber-for-php'));

$twig->addFunction(new \Twig\TwigFunction('get_class', 'get_class'));

/* Check if we have loaded variables */
if (isset($_GET['phonenumber']) && $_GET['phonenumber'] != '') {

    $input = array();
    $input['phonenumber'] = $_GET['phonenumber'];
    $input['country'] = (isset($_GET['country'])) ? $_GET['country'] : null;
    $input['language'] = (isset($_GET['language']) && $_GET['language'] != '') ? $_GET['language'] : 'en';
    $input['region'] = (isset($_GET['region']) && $_GET['region'] != '') ? $_GET['region'] : null;


    $phoneNumberUtil = \libphonenumber\PhoneNumberUtil::getInstance();
    $shortNumberInfo = \libphonenumber\ShortNumberInfo::getInstance();
    $phoneNumberGeocoder = \libphonenumber\geocoding\PhoneNumberOfflineGeocoder::getInstance();

    $validNumber = false;
    $validNumberForRegion = false;
    $possibleNumber = false;
    $isPossibleNumberWithReason = null;
    $geolocation = null;
    $phoneNumberToCarrierInfo = null;
    $timezone = null;

    try {
        $phoneNumber = $phoneNumberUtil->parse($input['phonenumber'], $input['country'], null, true);
        $possibleNumber = $phoneNumberUtil->isPossibleNumber($phoneNumber);
        $isPossibleNumberWithReason = $phoneNumberUtil->isPossibleNumberWithReason($phoneNumber);
        $validNumber = $phoneNumberUtil->isValidNumber($phoneNumber);
        $validNumberForRegion = $phoneNumberUtil->isValidNumberForRegion($phoneNumber, $input['country']);
        $phoneNumberRegion = $phoneNumberUtil->getRegionCodeForNumber($phoneNumber);
        $phoneNumberType = $phoneNumberUtil->getNumberType($phoneNumber);

        $geolocation = $phoneNumberGeocoder->getDescriptionForNumber(
            $phoneNumber,
            $input['language'],
            $input['region']
        );

        $phoneNumberToCarrierInfo = \libphonenumber\PhoneNumberToCarrierMapper::getInstance()->getNameForNumber(
            $phoneNumber,
            $input['language']
        );
        $timezone = \libphonenumber\PhoneNumberToTimeZonesMapper::getInstance()->getTimeZonesForNumber($phoneNumber);

    } catch (\Exception $e) {
        echo $twig->render(
            'error.twig',
            array(
                'input' => $input,
                'exception' => $e,
            )
        );
        exit;
    }

    $regions = $phoneNumberUtil->getSupportedRegions();

    asort($regions);

    $baseLanguagePath = __DIR__ . '/vendor/umpirsky/country-list/data/';

    $resolvedPath = realpath($baseLanguagePath . $input['language'] . '/country.php');
    if (strpos($resolvedPath, $baseLanguagePath) === 0 && file_exists($resolvedPath)) {
        $countries = require $resolvedPath;
    } else {
        $countries = require $baseLanguagePath . 'en/country.php';
    }

    echo $twig->render(
        'data.twig',
        array(
            'phoneNumberObj' => $phoneNumber,
            'phoneUtil' => $phoneNumberUtil,
            'possibleNumber' => $possibleNumber,
            'isPossibleNumberWithReason' => $isPossibleNumberWithReason,
            'validNumber' => $validNumber,
            'validNumberForRegion' => $validNumberForRegion,
            'phoneNumberRegion' => $phoneNumberRegion,
            'phoneNumberType' => $phoneNumberType,
            'geolocation' => $geolocation,
            'timezone' => $timezone,
            'carrierinfo' => $phoneNumberToCarrierInfo,
            'shortNumber' => $shortNumberInfo,
            'input' => $input,
            'regions' => $regions,
            'countries' => $countries,
        )
    );
} else {
    echo $twig->render('home.twig');
}
