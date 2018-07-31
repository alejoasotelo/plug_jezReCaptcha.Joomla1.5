<?php
/*
 * JEZ reCAPTCHA Integrator Joomla! 1.5 plugin
 *
 * @package        JEZ reCAPTCHA Integrator
 * @version        1.0.0
 * @author        JoomlaEZ.com
 * @copyright    Copyright (C) 2008 JoomlaEZ.com. All rights reserved
 * @license        Creative Commons Attribution-Noncommercial-Share Alike 3.0 Unported
 *
 * Please visit http://joomlaez.com/ for more information
 */

// no direct access
defined('_JEXEC') or die();

class plgSystemJezReCaptchaHelper
{
    // function to check if plugin is enabled and configured
    public function isEnabled()
    {
        static $enabled, $params;

        if (!isset($enabled)) {
            $enabled = JPluginHelper::isEnabled('system', 'jezReCaptcha');
        }

        if ($enabled) {
            // check for necessary params
            if (!isset($params)) {
                $plugin = &JPluginHelper::getPlugin('system', 'jezReCaptcha');
                $params = new JParameter($plugin->params);
            }

            if ($params->def('public_key', null) != null && $params->def('private_key', null) != null) {
                return $params;
            }
        }

        return false;
    }

    // function to parse captcha inclusion syntax
    public function parseInclusionSyntax(&$text, $params)
    {
        // inclusion syntax matching pattern
        $regex = '/{\s*captcha\s*}/i';

        // find all instances of inclusion syntax and put in $matches
        preg_match_all($regex, $text, $matches);
        $count = count($matches[0]);

        if ($count) {
            // replace the first inclusion syntax with real captcha
            $text = str_replace($matches[0][0], plgSystemJezReCaptchaHelper::renderCaptcha($params), $text);

            // captcha should be included only one time per page, clear any remaining inclusion syntax
            if ($count > 1) {
                plgSystemJezReCaptchaHelper::clearInclusionSyntax($text);
            }

            return true;
        }

        return false;
    }

    // function to clear captcha inclusion syntax
    public function clearInclusionSyntax(&$text)
    {
        // inclusion syntax matching pattern
        $regex = '/{\s*captcha\s*}/i';

        if (preg_match($regex, $text)) {
            $text = preg_replace($regex, '', $text);
            return true;
        }

        return false;
    }

    // function to render captcha
    public function renderCaptcha($params)
    {
        //require_once(dirname(__FILE__).DS.'recaptcha-php-1.10/recaptchalib.php');

        $publickey = trim($params->get('public_key')); // you got this from the signup page

        $captcha = '<div class="g-recaptcha" data-sitekey="' . $publickey . '" data-theme="' . $params->get('theme' . 'light') . '"></div>';
        $captcha .= '<script type="text/javascript" src="https://www.google.com/recaptcha/api.js?hl=es"></script>';

        return $captcha;
    }

    // function to verify captcha
    public function verifyCaptcha($params)
    {
        require_once dirname(dirname(__FILE__)) . DS . 'vendor/autoload.php';

        $privatekey = trim($params->get('private_key'));
        $recaptcha = new \ReCaptcha\ReCaptcha($privatekey);

        $gRecaptchaResponse = JRequest::getVar('g-recaptcha-response');
        $remoteAddr = JRequest::getVar('REMOTE_ADDR', '127.0.0.1', 'server');

        $resp = $recaptcha->verify($gRecaptchaResponse, $remoteAddr);

        if (!$resp->isSuccess()) {
            JError::raiseWarning('SOME_ERROR_CODE', 'Captcha mal ingresado, vuelva a ingresar las letras de la imagen correctamente. ');

            global $mainframe;
            $httpReferer = JRequest::getVar('HTTP_REFERER', JURI::base(true), 'server');
            $mainframe->redirect($httpReferer);
        }
    }

    // function to get document type
    public function getDocType()
    {
        static $docType;

        if (!isset($docType)) {
            $document = &JFactory::getDocument();
            $docType = $document->getType();
        }

        return $docType;
    }
}
