<?php

class GValidators{

	public static $codes = array();

	public static function isEmpty($value, $trim = false){
		return $value===null || $value===array() || $value==='' || $trim && is_scalar($value) && trim($value)==='';
	}

	/**
	 * Field is required
	 */
	public static function required($field, $value){
		if(self::isEmpty($value)){
			self::$codes[$field][] = "EMPTY";
			return false;
		}
		return true;
	}

	/**
	 * Checks if value entered is equal to 1 or 0, it also allows null values
	 *
	 * @param string $field The field to be tested
	 * @param mixed $value The field value to be tested
	 * @param array $params The parameters for the validator
	 */
	public static function boolean($field, $value, $params){

		$params = array_merge(array(
				'allowNull' => false,
				'falseValue' => 0,
				'trueValue' => 1
		), $params);

		if($params['allowNull'] || self::isEmpty($value))
			return true;

		if($value == $params['trueValue'] || ($value == $params['falseValue'] || !$value)){
			return true;
		}else{
			self::$codes[$field][] = "TF_OOR";
			return false;
		}
	}

	/**
	 * Detects the character length of a certain fields value
	 *
	 * @param $field
	 * @param $value
	 * @param $params
	 */
	public static function string($field, $value, $params){

		$params = array_merge(array(
				'allowEmpty' => true,
				'min' => null,
				'max' => null,
				'is' => null,
				'encoding' => null,
		), $params);

		if($params['allowEmpty'] && self::isEmpty($value)){
			return true;
		}

		if(function_exists('mb_strlen') && $params['encoding'])
			$str_length=mb_strlen($value, $params['encoding'] ? $params['encoding'] : 'UTF-8');
		else
			$str_length=strlen($value);

		if($params['min']){
			if($params['min'] > $str_length){ // Lower than min required
				self::$codes[$field][] = "CL_OOR";
				return false;
			}
		}

		if($params['max']){
			if($params['max'] < $str_length){
				self::$codes[$field][] = "CL_OOR";
				return false;
			}
		}
		return true;
	}

	public static function objExist($field, $value, $params){

		$params = array_merge(array(
				'allowEmpty' => true,
				'class' => null,
				'condition' => null,
				'field' => null,
				'notExist' => false
		), $params);

		if($params['allowEmpty'] && self::isEmpty($value))
			return true;

		$cName = $params['class'];
		$condition = isset($params['condition']) ? $params['condition'] : array();
		$object = $cName::model()->findOne(array_merge(array($params['field']=>$value), $condition));

		if($params['notExist']){
			if($object){
				return false;
			}else{
				self::$codes[$field][] = "IE_OBNFOUND";
				return true;
			}
		}else{
			if($object){
				self::$codes[$field][] = "NE_OBFOUND";
				return true;
			}else{
				return false;
			}
		}
	}

	public static function in($field, $value, $params){

		$params = array_merge(array(
				'allowEmpty' => true,
				'range' => array(),
		), $params);

		if($params['allowEmpty'] && self::isEmpty($value)){
			return true;
		}

		$found = false;
		foreach($params['range'] as $match){
			if($match == $value){
				$found = true;
			}
		}

		if(!$found){
			self::$codes[$field][] = 'IN_OOR';
			return false;
		}
		return true;
	}

	public static function nin($field, $value, $params){

		$params = array_merge(array(
				'allowEmpty' => true,
				'range' => array(),
		), $params);

		if($params['allowEmpty'] && self::isEmpty($value)){
			return true;
		}

		$found = false;
		foreach($params['range'] as $match){
			if($match == $value){
				$found = true;
			}
		}

		if($found){
			self::$codes[$field][] = "NIN_FOUND";
			return false;
		}
		return true;
	}

	public static function regex($field, $value, $params){

		$params = array_merge(array(
				'allowEmpty' => true,
				'pattern' => null,
				'nin' => false
		), $params);

		if($params['allowEmpty'] && self::isEmpty($value)){
			return true;
		}

		if($params['nin']){
			if(preg_match($params['pattern'], $value) > 0){
				self::$codes[$field][] = "REGEX_NOTVALID";
				return false;
			}
		}else{
			if(preg_match($params['pattern'], $value) <= 0 || preg_match($params['pattern'], $value) === false){
				self::$codes[$field][] = "REGEX_NOTVALID";
				return false;
			}
		}
		return true;
	}

	public static function compare($field, $value, $params){

		$params = array_merge(array(
				'allowEmpty' => true,
				'with' => true,
				'field' => null,
				'operator' => '=',
		), $params);

		if($params['allowEmpty'] && self::isEmpty($value)){
			return true;
		}

		$with_val = $params['with'];
		if($params['field']){
			$with_val = $this->{$params['with']};
		}

		switch($params['operator']){
			case '=':
			case '==':
				if($value == $with_val){
					return true;
				}
				break;
			case '!=':
				if($value != $with_val){
					return true;
				}
				break;
			case ">=":
				if($value >= $with_val){
					return true;
				}
				break;
			case ">":
				if($value > $with_val){
					return true;
				}
				break;
			case "<=":
				if($value <= $with_val){
					return true;
				}
				break;
			case "<":
				if($value < $with_val){
					return true;
				}
				break;
		}
		self::$codes[$field][] = "CMP_NOTMATCH";
		return false;
	}

	public static function number($field, $value, $params){

		$params = array_merge(array(
				'allowEmpty' => true,
				'integerOnly' => true,
				'max' => null,
				'min' => null,
				'intPattern' => '/^\s*[+-]?\d+\s*$/',
				'numPattern' => '/^\s*[-+]?[0-9]*\.?[0-9]+([eE][-+]?[0-9]+)?\s*$/'
		), $params);

		//var_dump($vlaue); exit();
		if($params['allowEmpty'] && self::isEmpty($value)){
			return true;
		}

		if($params['integerOnly']){
			if(preg_match($params['intPattern'], $value) > 0){
			}else{
				self::$codes[$field][] = "NOT_NUMERIC";
				return false;
			}
		}elseif(preg_match($params['numPattern'], $value) < 0 || !preg_match($params['numPattern'], $value)){
			self::$codes[$field][] = "NOT_NUMERIC";
			return false;
		}

		if($params['min']){
			if($value < $params['min']){
				self::$codes[$field][] = "RNG_OOR";
				return false;
			}
		}

		if($params['max']){
			if($value > $params['max']){
				self::$codes[$field][] = "RNG_OOR";
				return false;
			}
		}
		return true;
	}

	public static function url($field, $value, $params){

		$params = array_merge(array(
				'allowEmpty' => true,
		), $params);

		if($params['allowEmpty'] && self::isEmpty($value)){
			return true;
		}

		$parsed_url = parse_url($value);

		if(!$parsed_url){
			self::$codes[$field][] = "NOT_URL";
			return false;
		}

		if(isset($parsed_url['scheme'])){
			if(!isset($parsed_url['host'])){
				self::$codes[$field][] = "NOT_URL";
				return false;
			}else{
				return true;
			}
		}

		//if(!isset($parsed_url['path'])){
		//self::$codes[$field][] = "NOT_URL";
		return false;
		//}
		//return true;
	}

	public static function file($field, $value, $params){

		$params = array_merge(array(
				'allowEmpty' => true,
				'ext' => null,
				'size' => null,
				'type' => null
		), $params);

		if($params['allowEmpty'] && self::isEmpty($value)){
			return true;
		}

		$fieldValue = $value;

		if($fieldValue['error'] === UPLOAD_ERR_OK){
			if(isset($params['ext'])){
				$path = pathinfo($fieldValue['name']);

				$found = false;
				foreach($params['ext'] as $ext){
					if($ext == $path['extension'])
						$found = true;
				}

				if(!$found){
					self::$codes[$field][] = "FILE_EXT";
					return false;
				}
			}

			if(isset($params['size'])){
				if(isset($params['size']['gt'])){
					if($fieldValue['size'] < $params['size']['gt']){
						self::$codes[$field][] = "FILE_SIZE";
						return false;
					}
				}elseif(isset($params['size']['lt'])){
					if($fieldValue['size'] > $params['size']['lt']){
						self::$codes[$field][] = "FILE_SIZE";
						return false;
					}
				}
			}

			if(isset($params['type'])){
				if(preg_match("/".$params['type']."/i", $fieldValue['type']) === false || preg_match("/".$params['type']."/i", $fieldValue['type']) < 0){
					self::$codes[$field][] = "FILE_TYPE";
					return false;
				}
			}
		}else{
			switch ($fieldValue['error']) {
				case UPLOAD_ERR_INI_SIZE:
					self::$codes[$field][] = 'INI_SIZE_TOO_BIG';
					return false;
				case UPLOAD_ERR_FORM_SIZE:
					self::$codes[$field][] = 'MAX_FILE_TOO_BIG';
					return false;
				case UPLOAD_ERR_PARTIAL:
					self::$codes[$field][] = 'PARTIAL_UPLOAD';
					return false;
				case UPLOAD_ERR_NO_FILE:
					self::$codes[$field][] = 'NO_FILE';
					return false;
				case UPLOAD_ERR_NO_TMP_DIR:
					self::$codes[$field][] = 'NO_TMP_DIR';
					return false;
				case UPLOAD_ERR_CANT_WRITE:
					self::$codes[$field][] = 'CANT_WRITE';
					return false;
				case UPLOAD_ERR_EXTENSION:
					self::$codes[$field][] = 'ERR_EXTENSION';
					return false;
				default:
					self::$codes[$field][] = 'FILE_INVALID';
					return false;
			}
		}
		return true;
	}

	public static function tokenized($field, $value, $params){

		$params = array_merge(array(
				'allowEmpty' => true,
				'del' => '/[\s]*[,][\s]*/',
				'max' => null
		), $params);

		if($params['allowEmpty'] && self::isEmpty($value)){
			return true;
		}

		$ex_val = preg_split($params['del'], $value);

		if(isset($params['max'])){
			if(sizeof($ex_val) > $params['max']){
				self::$codes[$field][] = 'TOK_MAX';
				return false;
			}
		}
		return true;
	}


	public static function email($field, $value, $params = array()){

		$params = array_merge(array(
				'allowEmpty' => true,
		), $params);

		if($params['allowEmpty'] && self::isEmpty($value)){
			return true;
		}

		if(self::is_email($value)){
			return true;
		}
		return false;
	}

	public static function ip($field, $value, $params = array()){

		$params = array_merge(array(
				'allowEmpty' => true,
				'min' => null,
				'max' => null
		), $params);

		if($params['allowEmpty'] && self::isEmpty($value)){
			return true;
		}

		if($params['min'] && $params['max']){
			if(self::ip_in_range($params['min'].'-'.$params['max'])){
				return true;
			}
		}
	}

	public static function hash($field, $value, $params = array()){
		if(glue::http()->validateCsrfToken($value)){
			return true;
		}
		return false;
	}

	public static function safe($field, $value, $params = array()){
		return true; // Just do this so the field gets sent through
	}

	public static function date($field, $value, $params = array()){

		$params = array_merge(array(
				'format' => 'd/m/yyyy'
		), $params);

		// Lets tokenize the date field
		$date_parts = preg_split('/[-\/\s]+/', $value); // Accepted deliminators are -, / and space

		switch($params['format']){
			case 'd/m/yyyy':
				if(sizeof($date_parts) != 3){
					return false;
				}

				if(preg_match('/[1-32]/', $date_parts[0]) > 0 && preg_match('/[1-12]/', $date_parts[1]) > 0 && preg_match('/[0-9]{4}/', $date_parts[2]) && $date_parts[2] <= date('Y')){
					// If date matches formation and is not in the future in this case
					return true;
				}
				break;
		}
		return false;
	}

	/**
	 * HELPERS BUILT BY THIRD PARTIES
	 */


	/**
	 * To validate an email address according to RFC 5322 and others
	 *
	 * Copyright (c) 2008-2010, Dominic Sayers							<br>
	 * All rights reserved.
	 *
	 * Redistribution and use in source and binary forms, with or without modification,
	 * are permitted provided that the following conditions are met:
	 *
	 *     - Redistributions of source code must retain the above copyright notice,
	 *       this list of conditions and the following disclaimer.
	 *     - Redistributions in binary form must reproduce the above copyright notice,
	 *       this list of conditions and the following disclaimer in the documentation
	 *       and/or other materials provided with the distribution.
	 *     - Neither the name of Dominic Sayers nor the names of its contributors may be
	 *       used to endorse or promote products derived from this software without
	 *       specific prior written permission.
	 *
	 * THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS "AS IS" AND
	 * ANY EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT LIMITED TO, THE IMPLIED
	 * WARRANTIES OF MERCHANTABILITY AND FITNESS FOR A PARTICULAR PURPOSE ARE
	 * DISCLAIMED. IN NO EVENT SHALL THE COPYRIGHT HOLDER OR CONTRIBUTORS BE LIABLE FOR
	 * ANY DIRECT, INDIRECT, INCIDENTAL, SPECIAL, EXEMPLARY, OR CONSEQUENTIAL DAMAGES
	 * (INCLUDING, BUT NOT LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES;
	 * LOSS OF USE, DATA, OR PROFITS; OR BUSINESS INTERRUPTION) HOWEVER CAUSED AND ON
	 * ANY THEORY OF LIABILITY, WHETHER IN CONTRACT, STRICT LIABILITY, OR TORT
	 * (INCLUDING NEGLIGENCE OR OTHERWISE) ARISING IN ANY WAY OUT OF THE USE OF THIS
	 * SOFTWARE, EVEN IF ADVISED OF THE POSSIBILITY OF SUCH DAMAGE.
	 *
	 * @package	is_email
	 * @author	Dominic Sayers <dominic@sayers.cc>
	 * @copyright	2008-2010 Dominic Sayers
	 * @license	http://www.opensource.org/licenses/bsd-license.php BSD License
	 * @link	http://www.dominicsayers.com/isemail
	 * @version	1.18.1 - Releasing I got the version number wrong for the last release. No other change :-|
	 */

	// The quality of this code has been improved greatly by using PHPLint
	// Copyright (c) 2009 Umberto Salsi
	// This is free software; see the license for copying conditions.
	// More info: http://www.icosaedro.it/phplint/
	/*.
	 require_module 'standard';
	 require_module 'pcre';
	 .*/
	/*.mixed.*/ private static function is_email (/*.string.*/ $email, $checkDNS = false, $diagnose = false) {
	// Check that $email is a valid address. Read the following RFCs to understand the constraints:
	// 	(http://tools.ietf.org/html/rfc5322)
	// 	(http://tools.ietf.org/html/rfc3696)
	// 	(http://tools.ietf.org/html/rfc5321)
	// 	(http://tools.ietf.org/html/rfc4291#section-2.2)
	// 	(http://tools.ietf.org/html/rfc1123#section-2.1)

	if (!defined('ISEMAIL_VALID')) {
		define('ISEMAIL_VALID'			, 0);
		define('ISEMAIL_TOOLONG'		, 1);
		define('ISEMAIL_NOAT'			, 2);
		define('ISEMAIL_NOLOCALPART'		, 3);
		define('ISEMAIL_NODOMAIN'		, 4);
		define('ISEMAIL_ZEROLENGTHELEMENT'	, 5);
		define('ISEMAIL_BADCOMMENT_START'	, 6);
		define('ISEMAIL_BADCOMMENT_END'		, 7);
		define('ISEMAIL_UNESCAPEDDELIM'		, 8);
		define('ISEMAIL_EMPTYELEMENT'		, 9);
		define('ISEMAIL_UNESCAPEDSPECIAL'	, 10);
		define('ISEMAIL_LOCALTOOLONG'		, 11);
		define('ISEMAIL_IPV4BADPREFIX'		, 12);
		define('ISEMAIL_IPV6BADPREFIXMIXED'	, 13);
		define('ISEMAIL_IPV6BADPREFIX'		, 14);
		define('ISEMAIL_IPV6GROUPCOUNT'		, 15);
		define('ISEMAIL_IPV6DOUBLEDOUBLECOLON'	, 16);
		define('ISEMAIL_IPV6BADCHAR'		, 17);
		define('ISEMAIL_IPV6TOOMANYGROUPS'	, 18);
		define('ISEMAIL_TLD'			, 19);
		define('ISEMAIL_DOMAINEMPTYELEMENT'	, 20);
		define('ISEMAIL_DOMAINELEMENTTOOLONG'	, 21);
		define('ISEMAIL_DOMAINBADCHAR'		, 22);
		define('ISEMAIL_DOMAINTOOLONG'		, 23);
		define('ISEMAIL_TLDNUMERIC'		, 24);
		define('ISEMAIL_DOMAINNOTFOUND'		, 25);
		define('ISEMAIL_NOTDEFINED'		, 99);
	}

	// the upper limit on address lengths should normally be considered to be 254
	// 	(http://www.rfc-editor.org/errata_search.php?rfc=3696)
	// 	NB My erratum has now been verified by the IETF so the correct answer is 254
	//
	// The maximum total length of a reverse-path or forward-path is 256
	// characters (including the punctuation and element separators)
	// 	(http://tools.ietf.org/html/rfc5321#section-4.5.3.1.3)
	//	NB There is a mandatory 2-character wrapper round the actual address
	$emailLength = strlen($email);
	// revision 1.17: Max length reduced to 254 (see above)
	if ($emailLength > 254)			return $diagnose ? ISEMAIL_TOOLONG	: false;	// Too long

	// Contemporary email addresses consist of a "local part" separated from
	// a "domain part" (a fully-qualified domain name) by an at-sign ("@").
	// 	(http://tools.ietf.org/html/rfc3696#section-3)
	$atIndex = strrpos($email,'@');

	if ($atIndex === false)			return $diagnose ? ISEMAIL_NOAT		: false;	// No at-sign
	if ($atIndex === 0)			return $diagnose ? ISEMAIL_NOLOCALPART	: false;	// No local part
	if ($atIndex === $emailLength - 1)	return $diagnose ? ISEMAIL_NODOMAIN	: false;	// No domain part
	// revision 1.14: Length test bug suggested by Andrew Campbell of Gloucester, MA

	// Sanitize comments
	// - remove nested comments, quotes and dots in comments
	// - remove parentheses and dots from quoted strings
	$braceDepth	= 0;
	$inQuote	= false;
	$escapeThisChar	= false;

	for ($i = 0; $i < $emailLength; ++$i) {
		$char = $email[$i];
		$replaceChar = false;

		if ($char === '\\') {
			$escapeThisChar = !$escapeThisChar;	// Escape the next character?
		} else {
			switch ($char) {
				case '(':
					if ($escapeThisChar) {
						$replaceChar = true;
					} else {
						if ($inQuote) {
							$replaceChar = true;
						} else {
							if ($braceDepth++ > 0) $replaceChar = true;	// Increment brace depth
						}
					}

					break;
				case ')':
					if ($escapeThisChar) {
						$replaceChar = true;
					} else {
						if ($inQuote) {
							$replaceChar = true;
						} else {
							if (--$braceDepth > 0) $replaceChar = true;	// Decrement brace depth
							if ($braceDepth < 0) $braceDepth = 0;
						}
					}

					break;
				case '"':
					if ($escapeThisChar) {
						$replaceChar = true;
					} else {
						if ($braceDepth === 0) {
							$inQuote = !$inQuote;	// Are we inside a quoted string?
						} else {
							$replaceChar = true;
						}
					}

					break;
				case '.':	// Dots don't help us either
					if ($escapeThisChar) {
						$replaceChar = true;
					} else {
						if ($braceDepth > 0) $replaceChar = true;
					}

					break;
				default:
			}

			$escapeThisChar = false;
			//			if ($replaceChar) $email[$i] = 'x';	// Replace the offending character with something harmless
			// revision 1.12: Line above replaced because PHPLint doesn't like that syntax
			if ($replaceChar) $email = (string) substr_replace($email, 'x', $i, 1);	// Replace the offending character with something harmless
		}
	}

	$localPart	= substr($email, 0, $atIndex);
	$domain		= substr($email, $atIndex + 1);
	$FWS		= "(?:(?:(?:[ \\t]*(?:\\r\\n))?[ \\t]+)|(?:[ \\t]+(?:(?:\\r\\n)[ \\t]+)*))";	// Folding white space
	// Let's check the local part for RFC compliance...
	//
	// local-part      =       dot-atom / quoted-string / obs-local-part
	// obs-local-part  =       word *("." word)
	// 	(http://tools.ietf.org/html/rfc5322#section-3.4.1)
	//
	// Problem: need to distinguish between "first.last" and "first"."last"
	// (i.e. one element or two). And I suck at regexes.
	$dotArray	= /*. (array[int]string) .*/ preg_split('/\\.(?=(?:[^\\"]*\\"[^\\"]*\\")*(?![^\\"]*\\"))/m', $localPart);
	$partLength	= 0;

	foreach ($dotArray as $element) {
		// Remove any leading or trailing FWS
		$element	= preg_replace("/^$FWS|$FWS\$/", '', $element);
		$elementLength	= strlen($element);

		if ($elementLength === 0)								return $diagnose ? ISEMAIL_ZEROLENGTHELEMENT	: false;	// Can't have empty element (consecutive dots or dots at the start or end)
		// revision 1.15: Speed up the test and get rid of "unitialized string offset" notices from PHP

		// We need to remove any valid comments (i.e. those at the start or end of the element)
		if ($element[0] === '(') {
			$indexBrace = strpos($element, ')');
			if ($indexBrace !== false) {
				if (preg_match('/(?<!\\\\)[\\(\\)]/', substr($element, 1, $indexBrace - 1)) > 0) {
					return $diagnose ? ISEMAIL_BADCOMMENT_START	: false;	// Illegal characters in comment
				}
				$element	= substr($element, $indexBrace + 1, $elementLength - $indexBrace - 1);
				$elementLength	= strlen($element);
			}
		}

		if ($element[$elementLength - 1] === ')') {
			$indexBrace = strrpos($element, '(');
			if ($indexBrace !== false) {
				if (preg_match('/(?<!\\\\)(?:[\\(\\)])/', substr($element, $indexBrace + 1, $elementLength - $indexBrace - 2)) > 0) {
					return $diagnose ? ISEMAIL_BADCOMMENT_END	: false;	// Illegal characters in comment
				}
				$element	= substr($element, 0, $indexBrace);
				$elementLength	= strlen($element);
			}
		}

		// Remove any leading or trailing FWS around the element (inside any comments)
		$element = preg_replace("/^$FWS|$FWS\$/", '', $element);

		// What's left counts towards the maximum length for this part
		if ($partLength > 0) $partLength++;	// for the dot
		$partLength += strlen($element);

		// Each dot-delimited component can be an atom or a quoted string
		// (because of the obs-local-part provision)
		if (preg_match('/^"(?:.)*"$/s', $element) > 0) {
			// Quoted-string tests:
			//
			// Remove any FWS
			$element = preg_replace("/(?<!\\\\)$FWS/", '', $element);
			// My regex skillz aren't up to distinguishing between \" \\" \\\" \\\\" etc.
			// So remove all \\ from the string first...
			$element = preg_replace('/\\\\\\\\/', ' ', $element);
			if (preg_match('/(?<!\\\\|^)["\\r\\n\\x00](?!$)|\\\\"$|""/', $element) > 0)	return $diagnose ? ISEMAIL_UNESCAPEDDELIM	: false;	// ", CR, LF and NUL must be escaped, "" is too short
		} else {
			// Unquoted string tests:
			//
			// Period (".") may...appear, but may not be used to start or end the
			// local part, nor may two or more consecutive periods appear.
			// 	(http://tools.ietf.org/html/rfc3696#section-3)
			//
			// A zero-length element implies a period at the beginning or end of the
			// local part, or two periods together. Either way it's not allowed.
			if ($element === '')								return $diagnose ? ISEMAIL_EMPTYELEMENT	: false;	// Dots in wrong place

			// Any ASCII graphic (printing) character other than the
			// at-sign ("@"), backslash, double quote, comma, or square brackets may
			// appear without quoting.  If any of that list of excluded characters
			// are to appear, they must be quoted
			// 	(http://tools.ietf.org/html/rfc3696#section-3)
			//
			// Any excluded characters? i.e. 0x00-0x20, (, ), <, >, [, ], :, ;, @, \, comma, period, "
			if (preg_match('/[\\x00-\\x20\\(\\)<>\\[\\]:;@\\\\,\\."]/', $element) > 0)	return $diagnose ? ISEMAIL_UNESCAPEDSPECIAL	: false;	// These characters must be in a quoted string
		}
	}

	if ($partLength > 64) return $diagnose ? ISEMAIL_LOCALTOOLONG	: false;	// Local part must be 64 characters or less

	// Now let's check the domain part...

	// The domain name can also be replaced by an IP address in square brackets
	// 	(http://tools.ietf.org/html/rfc3696#section-3)
	// 	(http://tools.ietf.org/html/rfc5321#section-4.1.3)
	// 	(http://tools.ietf.org/html/rfc4291#section-2.2)
	if (preg_match('/^\\[(.)+]$/', $domain) === 1) {
		// It's an address-literal
		$addressLiteral = substr($domain, 1, strlen($domain) - 2);
		$matchesIP	= array();

		// Extract IPv4 part from the end of the address-literal (if there is one)
		if (preg_match('/\\b(?:(?:25[0-5]|2[0-4][0-9]|[01]?[0-9][0-9]?)\\.){3}(?:25[0-5]|2[0-4][0-9]|[01]?[0-9][0-9]?)$/', $addressLiteral, $matchesIP) > 0) {
			$index = strrpos($addressLiteral, $matchesIP[0]);

			if ($index === 0) {
				// Nothing there except a valid IPv4 address, so...
				return $diagnose ? ISEMAIL_VALID : true;
			} else {
				// Assume it's an attempt at a mixed address (IPv6 + IPv4)
				if ($addressLiteral[$index - 1] !== ':')	return $diagnose ? ISEMAIL_IPV4BADPREFIX	: false;	// Character preceding IPv4 address must be ':'
				if (substr($addressLiteral, 0, 5) !== 'IPv6:')	return $diagnose ? ISEMAIL_IPV6BADPREFIXMIXED	: false;	// RFC5321 section 4.1.3

				$IPv6		= substr($addressLiteral, 5, ($index ===7) ? 2 : $index - 6);
				$groupMax	= 6;
			}
		} else {
			// It must be an attempt at pure IPv6
			if (substr($addressLiteral, 0, 5) !== 'IPv6:')		return $diagnose ? ISEMAIL_IPV6BADPREFIX	: false;	// RFC5321 section 4.1.3
			$IPv6 = substr($addressLiteral, 5);
			$groupMax = 8;
		}

		$groupCount	= preg_match_all('/^[0-9a-fA-F]{0,4}|\\:[0-9a-fA-F]{0,4}|(.)/', $IPv6, $matchesIP);
		$index		= strpos($IPv6,'::');

		if ($index === false) {
			// We need exactly the right number of groups
			if ($groupCount !== $groupMax)				return $diagnose ? ISEMAIL_IPV6GROUPCOUNT	: false;	// RFC5321 section 4.1.3
		} else {
			if ($index !== strrpos($IPv6,'::'))			return $diagnose ? ISEMAIL_IPV6DOUBLEDOUBLECOLON : false;	// More than one '::'
			$groupMax = ($index === 0 || $index === (strlen($IPv6) - 2)) ? $groupMax : $groupMax - 1;
			if ($groupCount > $groupMax)				return $diagnose ? ISEMAIL_IPV6TOOMANYGROUPS	: false;	// Too many IPv6 groups in address
		}

		// Check for unmatched characters
		array_multisort($matchesIP[1], SORT_DESC);
		if ($matchesIP[1][0] !== '')					return $diagnose ? ISEMAIL_IPV6BADCHAR		: false;	// Illegal characters in address

		// It's a valid IPv6 address, so...
		return $diagnose ? ISEMAIL_VALID : true;
	} else {
		// It's a domain name...

		// The syntax of a legal Internet host name was specified in RFC-952
		// One aspect of host name syntax is hereby changed: the
		// restriction on the first character is relaxed to allow either a
		// letter or a digit.
		// 	(http://tools.ietf.org/html/rfc1123#section-2.1)
		//
		// NB RFC 1123 updates RFC 1035, but this is not currently apparent from reading RFC 1035.
		//
		// Most common applications, including email and the Web, will generally not
		// permit...escaped strings
		// 	(http://tools.ietf.org/html/rfc3696#section-2)
		//
		// the better strategy has now become to make the "at least one period" test,
		// to verify LDH conformance (including verification that the apparent TLD name
		// is not all-numeric)
		// 	(http://tools.ietf.org/html/rfc3696#section-2)
		//
		// Characters outside the set of alphabetic characters, digits, and hyphen MUST NOT appear in domain name
		// labels for SMTP clients or servers
		// 	(http://tools.ietf.org/html/rfc5321#section-4.1.2)
		//
		// RFC5321 precludes the use of a trailing dot in a domain name for SMTP purposes
		// 	(http://tools.ietf.org/html/rfc5321#section-4.1.2)
		$dotArray	= /*. (array[int]string) .*/ preg_split('/\\.(?=(?:[^\\"]*\\"[^\\"]*\\")*(?![^\\"]*\\"))/m', $domain);
		$partLength	= 0;
		$element	= ''; // Since we use $element after the foreach loop let's make sure it has a value
		// revision 1.13: Line above added because PHPLint now checks for Definitely Assigned Variables

		if (count($dotArray) === 1)					return $diagnose ? ISEMAIL_TLD	: false;	// Mail host can't be a TLD (cite? What about localhost?)

		foreach ($dotArray as $element) {
			// Remove any leading or trailing FWS
			$element	= preg_replace("/^$FWS|$FWS\$/", '', $element);
			$elementLength	= strlen($element);

			// Each dot-delimited component must be of type atext
			// A zero-length element implies a period at the beginning or end of the
			// local part, or two periods together. Either way it's not allowed.
			if ($elementLength === 0)				return $diagnose ? ISEMAIL_DOMAINEMPTYELEMENT	: false;	// Dots in wrong place
			// revision 1.15: Speed up the test and get rid of "unitialized string offset" notices from PHP

			// Then we need to remove all valid comments (i.e. those at the start or end of the element
			if ($element[0] === '(') {
				$indexBrace = strpos($element, ')');
				if ($indexBrace !== false) {
					if (preg_match('/(?<!\\\\)[\\(\\)]/', substr($element, 1, $indexBrace - 1)) > 0) {
						// revision 1.17: Fixed name of constant (also spotted by turboflash - thanks!)
						return $diagnose ? ISEMAIL_BADCOMMENT_START	: false;	// Illegal characters in comment
					}
					$element	= substr($element, $indexBrace + 1, $elementLength - $indexBrace - 1);
					$elementLength	= strlen($element);
				}
			}

			if ($element[$elementLength - 1] === ')') {
				$indexBrace = strrpos($element, '(');
				if ($indexBrace !== false) {
					if (preg_match('/(?<!\\\\)(?:[\\(\\)])/', substr($element, $indexBrace + 1, $elementLength - $indexBrace - 2)) > 0)
						// revision 1.17: Fixed name of constant (also spotted by turboflash - thanks!)
						return $diagnose ? ISEMAIL_BADCOMMENT_END	: false;	// Illegal characters in comment

					$element	= substr($element, 0, $indexBrace);
					$elementLength	= strlen($element);
				}
			}

			// Remove any leading or trailing FWS around the element (inside any comments)
			$element = preg_replace("/^$FWS|$FWS\$/", '', $element);

			// What's left counts towards the maximum length for this part
			if ($partLength > 0) $partLength++;	// for the dot
			$partLength += strlen($element);

			// The DNS defines domain name syntax very generally -- a
			// string of labels each containing up to 63 8-bit octets,
			// separated by dots, and with a maximum total of 255
			// octets.
			// 	(http://tools.ietf.org/html/rfc1123#section-6.1.3.5)
			if ($elementLength > 63)				return $diagnose ? ISEMAIL_DOMAINELEMENTTOOLONG	: false;	// Label must be 63 characters or less

			// Any ASCII graphic (printing) character other than the
			// at-sign ("@"), backslash, double quote, comma, or square brackets may
			// appear without quoting.  If any of that list of excluded characters
			// are to appear, they must be quoted
			// 	(http://tools.ietf.org/html/rfc3696#section-3)
			//
			// If the hyphen is used, it is not permitted to appear at
			// either the beginning or end of a label.
			// 	(http://tools.ietf.org/html/rfc3696#section-2)
			//
			// Any excluded characters? i.e. 0x00-0x20, (, ), <, >, [, ], :, ;, @, \, comma, period, "
			if (preg_match('/[\\x00-\\x20\\(\\)<>\\[\\]:;@\\\\,\\."]|^-|-$/', $element) > 0) {
				return $diagnose ? ISEMAIL_DOMAINBADCHAR	: false;
			}
		}

		if ($partLength > 255) 						return $diagnose ? ISEMAIL_DOMAINTOOLONG	: false;	// Domain part must be 255 characters or less (http://tools.ietf.org/html/rfc1123#section-6.1.3.5)

		if (preg_match('/^[0-9]+$/', $element) > 0)			return $diagnose ? ISEMAIL_TLDNUMERIC		: false;	// TLD can't be all-numeric (http://www.apps.ietf.org/rfc/rfc3696.html#sec-2)

		// Check DNS?
		if ($checkDNS && function_exists('checkdnsrr')) {
			if (!(checkdnsrr($domain, 'A') || checkdnsrr($domain, 'MX'))) {
				return $diagnose ? ISEMAIL_DOMAINNOTFOUND	: false;	// Domain doesn't actually exist
			}
		}
	}

	// Eliminate all other factors, and the one which remains must be the truth.
	// 	(Sherlock Holmes, The Sign of Four)
	return $diagnose ? ISEMAIL_VALID : true;
	}


	/**
	 * @author Paul Gregg <pgregg@pgregg.com>
	 * @copyright 10 January 2008
	 * @version 1.2
	 * @link http://www.pgregg.com/projects/php/ip_in_range/
	 * @link http://www.pgregg.com/donate/
	 *
	 * @license This software is Donationware - if you feel you have benefited from the use of this tool then please consider a donation.
	 *
	 * @tutorial ip_in_range.php - Function to determine if an IP is located in a
	 *                   specific range as specified via several alternative
	 *                   formats.
	 *
	 * Network ranges can be specified as:
	 * 1. Wildcard format:     1.2.3.*
	 * 2. CIDR format:         1.2.3/24  OR  1.2.3.4/255.255.255.0
	 * 3. Start-End IP format: 1.2.3.0-1.2.3.255
	 *
	 * Return value BOOLEAN : ip_in_range($ip, $range);
	 *
	 * Please do not remove this header, or source attibution from this file.
	 */


	// decbin32
	// In order to simplify working with IP addresses (in binary) and their
	// netmasks, it is easier to ensure that the binary strings are padded
	// with zeros out to 32 characters - IP addresses are 32 bit numbers
	Function decbin32 ($dec) {
		return str_pad(decbin($dec), 32, '0', STR_PAD_LEFT);
	}

	// ip_in_range
	// This function takes 2 arguments, an IP address and a "range" in several
	// different formats.
	// Network ranges can be specified as:
	// 1. Wildcard format:     1.2.3.*
	// 2. CIDR format:         1.2.3/24  OR  1.2.3.4/255.255.255.0
	// 3. Start-End IP format: 1.2.3.0-1.2.3.255
	// The function will return true if the supplied IP is within the range.
	// Note little validation is done on the range inputs - it expects you to
	// use one of the above 3 formats.
	Function ip_in_range($ip, $range) {
		if (strpos($range, '/') !== false) {
			// $range is in IP/NETMASK format
			list($range, $netmask) = explode('/', $range, 2);
			if (strpos($netmask, '.') !== false) {
				// $netmask is a 255.255.0.0 format
				$netmask = str_replace('*', '0', $netmask);
				$netmask_dec = ip2long($netmask);
				return ( (ip2long($ip) & $netmask_dec) == (ip2long($range) & $netmask_dec) );
			} else {
				// $netmask is a CIDR size block
				// fix the range argument
				$x = explode('.', $range);
				while(count($x)<4) $x[] = '0';
				list($a,$b,$c,$d) = $x;
				$range = sprintf("%u.%u.%u.%u", empty($a)?'0':$a, empty($b)?'0':$b,empty($c)?'0':$c,empty($d)?'0':$d);
				$range_dec = ip2long($range);
				$ip_dec = ip2long($ip);

				# Strategy 1 - Create the netmask with 'netmask' 1s and then fill it to 32 with 0s
				#$netmask_dec = bindec(str_pad('', $netmask, '1') . str_pad('', 32-$netmask, '0'));

				# Strategy 2 - Use math to create it
				$wildcard_dec = pow(2, (32-$netmask)) - 1;
				$netmask_dec = ~ $wildcard_dec;

				return (($ip_dec & $netmask_dec) == ($range_dec & $netmask_dec));
			}
		} else {
			// range might be 255.255.*.* or 1.2.3.0-1.2.3.255
			if (strpos($range, '*') !==false) { // a.b.*.* format
				// Just convert to A-B format by setting * to 0 for A and 255 for B
				$lower = str_replace('*', '0', $range);
				$upper = str_replace('*', '255', $range);
				$range = "$lower-$upper";
			}

			if (strpos($range, '-')!==false) { // A-B format
				list($lower, $upper) = explode('-', $range, 2);
				$lower_dec = (float)sprintf("%u",ip2long($lower));
				$upper_dec = (float)sprintf("%u",ip2long($upper));
				$ip_dec = (float)sprintf("%u",ip2long($ip));
				return ( ($ip_dec>=$lower_dec) && ($ip_dec<=$upper_dec) );
			}

			//echo 'Range argument is not in 1.2.3.4/24 or 1.2.3.4/255.255.255.0 format';
			return false;
		}

	}
}