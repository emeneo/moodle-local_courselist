<?php
require_once(dirname(__FILE__) . '/../../config.php');
require_once($CFG->libdir . '/adminlib.php');

// Sanitize the lang parameter
$lang = optional_param('lang', '', PARAM_ALPHANUM);

// Ensure the HTTP_REFERER is valid and safe
if (isset($_SERVER['HTTP_REFERER']) && strpos($_SERVER['HTTP_REFERER'], $CFG->wwwroot) === 0) {
    $redirectUrl = $_SERVER['HTTP_REFERER'];
} else {
    $redirectUrl = $CFG->wwwroot; // Fallback to the site's homepage
}

// Add lang parameter if it exists
if (!empty($lang)) {
    $tmp = explode("?", $redirectUrl);
    if (count($tmp) <= 1) {
        $redirectUrl .= "?lang=" . $lang;
    } else {
        $redirectUrl .= "&lang=" . $lang;
    }
}

// Use Moodle's redirect function
redirect($redirectUrl);