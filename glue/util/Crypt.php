<?php

namespace glue\util;

class Crypt
{
	public $rounds;
	public $mode;

	private $_randomState;

	public static function AES_encrypt256($blurb)
	{
	    $iv_size = mcrypt_get_iv_size(MCRYPT_RIJNDAEL_256, MCRYPT_MODE_ECB);
	    $iv = mcrypt_create_iv($iv_size, MCRYPT_RAND);
	    $key = "S4M__1-L-2_-+M6N__00c=++./..#+";
	    return mcrypt_encrypt(MCRYPT_RIJNDAEL_256, $key, $blurb, MCRYPT_MODE_ECB, $iv);
	}

	public static function AES_decrypt256($blurb)
	{
	    $iv_size = mcrypt_get_iv_size(MCRYPT_RIJNDAEL_256, MCRYPT_MODE_ECB);
	    $iv = mcrypt_create_iv($iv_size, MCRYPT_RAND);
	    $key = "S4M__1-L-2_-+M6N__00c=++./..#+";
	    return trim(mcrypt_decrypt(MCRYPT_RIJNDAEL_256, $key, $blurb, MCRYPT_MODE_ECB, $iv));
	}

	public static function blowfish_hash($input, $rounds = 15)
	{
		$o = new \glue\util\Crypt();
		$o->mode = 'blowfish';
		$o->rounds = $rounds;
		return $o->hash($input);
	}

	public static function verify($input, $existingHash)
	{
		$hash = crypt($input, $existingHash);
		return $hash === $existingHash;
	}

	public function hash($input)
	{
		$hash = crypt($input, $this->getSalt());
		if(strlen($hash) > 13){
			return $hash;
		}
		return false;
	}

	private function getSalt()
	{
		if($this->mode == 'sha512'){
			$salt = sprintf('$6$rounds=%02d$', $this->rounds);
		}else{
			$salt = sprintf('$2a$%02d$', $this->rounds);
		}

		$bytes = $this->getRandomBytes(16);
		$salt .= $this->encodeBytes($bytes);
		return $salt;
	}

	private function getRandomBytes($count)
	{
		$bytes = '';

		if(function_exists('openssl_random_pseudo_bytes') &&
		(strtoupper(substr(PHP_OS, 0, 3)) !== 'WIN')) { // OpenSSL slow on Win
			$bytes = openssl_random_pseudo_bytes($count);
		}

		if($bytes === '' && is_readable('/dev/urandom') &&
		($hRand = @fopen('/dev/urandom', 'rb')) !== FALSE) {
			$bytes = fread($hRand, $count);
			fclose($hRand);
		}

		if(strlen($bytes) < $count) {
			$bytes = '';

			if($this->_randomState === null) {
				$this->_randomState = microtime();
				if(function_exists('getmypid')) {
					$this->_randomState .= getmypid();
				}
			}

			for($i = 0; $i < $count; $i += 16) {
				$this->_randomState = md5(microtime() . $this->_randomState);

				if (PHP_VERSION >= '5') {
					$bytes .= md5($this->_randomState, true);
				} else {
					$bytes .= pack('H*', md5($this->_randomState));
				}
			}

			$bytes = substr($bytes, 0, $count);
		}

		return $bytes;
	}

	private function encodeBytes($input)
	{
		// The following is code from the PHP Password Hashing Framework
		$itoa64 = './ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789';

		$output = '';
		$i = 0;
		do {
			$c1 = ord($input[$i++]);
			$output .= $itoa64[$c1 >> 2];
			$c1 = ($c1 & 0x03) << 4;
			if ($i >= 16) {
				$output .= $itoa64[$c1];
				break;
			}

			$c2 = ord($input[$i++]);
			$c1 |= $c2 >> 4;
			$output .= $itoa64[$c1];
			$c1 = ($c2 & 0x0f) << 2;

			$c2 = ord($input[$i++]);
			$c1 |= $c2 >> 6;
			$output .= $itoa64[$c1];
			$output .= $itoa64[$c2 & 0x3f];
		} while (1);

		return $output;
	}

	public static function generate_new_pass()
	{
		$length=9; // Length of the returned password
		$strength=8; // A strength denominator

		$vowels = 'aeuy'; // Vowels to use
		$consonants = 'bdghjmnpqrstvz'; // Consonants to use

		// Repitition throughout the strengths to make the new password
		if ($strength & 1) {
			$consonants .= 'BDGHJLMNPQRSTVWXZ';
		}
		if ($strength & 2) {
			$vowels .= "AEUY";
		}
		if ($strength & 4) {
			$consonants .= '23456789';
		}
		if ($strength & 8) {
			$consonants .= '@#$%';
		}

		// Randomise the placement of text entities
		$password = '';
		$alt = time() % 2;
		for ($i = 0; $i < $length; $i++) {
			if ($alt == 1) {
				$password .= $consonants[(rand() % strlen($consonants))];
				$alt = 0;
			} else {
				$password .= $vowels[(rand() % strlen($vowels))];
				$alt = 1;
			}
		}

		// Return the password
		return $password;
	}
}
