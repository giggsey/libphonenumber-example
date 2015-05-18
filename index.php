<?php

require __DIR__ . '/vendor/autoload.php';

/* Load Twig */
$twig = new Twig_Environment(new Twig_Loader_Filesystem(__DIR__ . '/templates/'));

$twig->addGlobal('baseurl', $_SERVER['SCRIPT_NAME']);

/* Parse installed.json to get versions */
$versionInfo = json_decode(file_get_contents(__DIR__ . '/vendor/composer/installed.json'), true);

$versionDetails = array();

foreach ($versionInfo as $r) {
    $versionDetails[$r['name']] = array(
        'version' => $r['version'],
        'actual' => $r['dist']['reference']
    );
}

$twig->addGlobal('version', $versionDetails);

$twig->addFunction('get_class', new Twig_Function_Function('get_class'));

$twig->addFunction(
    new Twig_SimpleFunction(
        'elixir', function ($file) {
        // Borrowed from Laravel
        $manifest = json_decode(file_get_contents(__DIR__ . '/build/rev-manifest.json'), true);
        if (isset($manifest[$file])) {
            return 'build/' . $manifest[$file];
        }

        throw new InvalidArgumentException("File {$file} not defined in asset manifest.");
    }
    )
);


/* Check if we have loaded variables */
if (isset($_GET['phonenumber']) && $_GET['phonenumber'] != '' && isset($_GET['country']) && $_GET['country'] != '') {

    $input = array();
    $input['phonenumber'] = $_GET['phonenumber'];
    $input['country'] = $_GET['country'];
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
            'input' => $input
        )
    );
} else {
    echo $twig->render('home.twig');
}