<?php
/*
 * JEZ reCAPTCHA Integrator Joomla! 1.5 plugin
 *
 * @package		JEZ reCAPTCHA Integrator
 * @version		1.0.0
 * @author		JoomlaEZ.com
 * @copyright	Copyright (C) 2008 JoomlaEZ.com. All rights reserved
 * @license		Creative Commons Attribution-Noncommercial-Share Alike 3.0 Unported
 *
 * Please visit http://joomlaez.com/ for more information
 */

// no direct access
defined('_JEXEC') or die();

class plgSystemJezReCaptchaHelper {
	// function to check if plugin is enabled and configured
	function isEnabled() {
		static $enabled, $params;

		if (!isset($enabled))
			$enabled = JPluginHelper::isEnabled('system', 'jezReCaptcha');

		if ($enabled) {
			// check for necessary params
			if (!isset($params)) {
				$plugin	= &JPluginHelper::getPlugin('system', 'jezReCaptcha');
				$params	= new JParameter($plugin->params);
			}

			if ($params->def('public_key', null) != null && $params->def('private_key', null) != null)
				return $params;
		}

		return false;
	}

	// function to parse captcha inclusion syntax
	function parseInclusionSyntax(&$text, $params) {
		// inclusion syntax matching pattern
		$regex = '/{\s*captcha\s*}/i';

	 	// find all instances of inclusion syntax and put in $matches
		preg_match_all($regex, $text, $matches);
		$count = count($matches[0]);

		if ($count) {
			// replace the first inclusion syntax with real captcha
			$text = str_replace($matches[0][0], plgSystemJezReCaptchaHelper::renderCaptcha($params), $text);

			// captcha should be included only one time per page, clear any remaining inclusion syntax
			if ($count > 1)
				plgSystemJezReCaptchaHelper::clearInclusionSyntax($text);

			return true;
		}

		return false;
	}

	// function to clear captcha inclusion syntax
	function clearInclusionSyntax(&$text) {
		// inclusion syntax matching pattern
		$regex = '/{\s*captcha\s*}/i';

		if (preg_match($regex, $text)) {
			$text = preg_replace($regex, '', $text);
			return true;
		}

		return false;
	}

	// function to render captcha
	function renderCaptcha($params) {
		require_once(dirname(__FILE__).DS.'recaptcha-php-1.10/recaptchalib.php');

		$publickey	= trim($params->get('public_key')); // you got this from the signup page
		
		$theme = '';
		if($params->get('theme') == 'custom'){
		
			$theme	= "<script> var RecaptchaOptions = {
						theme	: 'custom',
						lang	: 'es'
						};
						</script>";
			$captcha = $theme . '
						<div id="recaptcha_image"></div>
						<div style="line-height: 2em;"><a href="javascript:Recaptcha.reload()">¿No se puede leer bien?</a></div> 
						<div style="height: 28px;">
							<label for="recaptcha_response_field" style="float: left; height: 20px; margin-top: 10px; line-height: 20px;">Ingrese las 2 palabras:</label>
							<input type="text" id="recaptcha_response_field" name="recaptcha_response_field"  style="margin-top: 10px; float: right;"/>
						</div>			
						<script type="text/javascript" src="http://www.google.com/recaptcha/api/challenge?k='.$publickey.'"></script>
						<noscript>
							<iframe src="http://www.google.com/recaptcha/api/noscript?k='.$publickey.'"> height="300" width="500" frameborder="0"></iframe>
							<textarea name="recaptcha_challenge_field" rows="3" cols="40"></textarea>
							<input type="hidden" name="recaptcha_response_field" value="manual_challenge" />
						</noscript>
						';
		}	
		else
			$captcha = recaptcha_get_html($publickey);
		
		

		return $captcha;
	}

	// function to verify captcha
	function verifyCaptcha($params) {
		// get captcha data
		$challenge = JRequest::getCmd("recaptcha_challenge_field", null, 'POST');
		$challenge = $challenge == null ? JRequest::getCmd("recaptcha_challenge_field", null, 'GET') : $challenge;

		// do we have captcha data to verify with reCAPTCHA?
		if ($challenge != null) {
			require_once(dirname(__FILE__).DS.'recaptcha-php-1.10/recaptchalib.php');

			// get user response
			$response = JRequest::getCmd("recaptcha_response_field", null, 'POST');
			$response = $response == null ? JRequest::getCmd("recaptcha_response_field", null, 'GET') : $response;

			$privatekey	= trim($params->get('private_key')); // you got this from the signup page
			$remoteAddr	= JRequest::getVar('REMOTE_ADDR', '127.0.0.1', 'server');
			$resp		= recaptcha_check_answer($privatekey, $remoteAddr, $challenge, $response);

			// if verification fail, generate error message then redirect to previous page
			if (!$resp->is_valid) {
				JError::raiseWarning('SOME_ERROR_CODE', 'Captcha mal ingresado, vuelva a ingresar las letras de la imagen correctamente. ');
                      
				global $mainframe;
				$httpReferer = JRequest::getVar('HTTP_REFERER', JURI::base(true), 'server');
				$mainframe->redirect($httpReferer);
			}
		}
	}

	// function to get document type
	function getDocType() {
		static $docType;

		if (!isset($docType)) {
			$document	= &JFactory::getDocument();
			$docType	= $document->getType();
		}

		return $docType;
	}
}
?>