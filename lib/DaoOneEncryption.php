<?php

namespace eftec;

use Exception;

/**
 * This class is used for encryption.  It could encrypt (two ways).
 * Class DaoOneEncryption
 * @version 3.26 20190206
 * @package eftec
 * @author Jorge Castro Castillo
 * @copyright (c) Jorge Castro C. MIT License  https://github.com/EFTEC/DaoOne
 * @see https://github.com/EFTEC/DaoOne
 */
class DaoOneEncryption
{
	//<editor-fold desc="encryption fields">
	/** @var bool Encryption enabled */
	var $encEnabled = false;
	/** @var string Encryption password */
	var $encPassword = '';
	/** @var string Encryption salt */
	var $encSalt = '';
	/**
	 * @var bool If iv is true then it is generated randomly, otherwise is it generated via md5
	 * If true, then the encrypted value is always different (but the decryption yields the same value).
	 * If false, then the value encrypted is the same for the same value.
	 * Set to false if you want a deterministic value (it always returns the same value)
	 */
	var $iv=true;
	/** @var string Encryption method, See http://php.net/manual/en/function.openssl-get-cipher-methods.php */
	var $encMethod = '';

	/**
	 * DaoOneEncryption constructor.
	 * @param string $encPassword
	 * @param string $encSalt
	 * @param bool $iv if true it uses true and the each encryption is different (even for the same value) but it is not deterministic.
	 * @param string $encMethod Example : AES-128-CTR @see http://php.net/manual/en/function.openssl-get-cipher-methods.php
	 */
	public function __construct(string $encPassword, string $encSalt=null, bool $iv=true, string $encMethod='AES-128-CTR')
	{
		
		$this->encPassword = $encPassword;
		$this->encSalt = $encSalt??$encPassword; // if null the it uses the same password
		$this->iv = $iv;
		$this->encMethod = $encMethod;
	}
	//</editor-fold>
	

	/**
	 * It is a two way decryption
	 * @param $data
	 * @return bool|string
	 */
	public function decrypt($data)
	{
		$data=base64_decode(str_replace(array('-', '_'),array('+', '/'),$data));
		if (!$this->encEnabled) return $data; // no encryption
		$iv_strlen = 2 * openssl_cipher_iv_length($this->encMethod);
		if (preg_match("/^(.{" . $iv_strlen . "})(.+)$/", $data, $regs)) {
			try {
				list(, $iv, $crypted_string) = $regs;
				$decrypted_string = openssl_decrypt($crypted_string, $this->encMethod, $this->encPassword, 0, hex2bin($iv));
				return substr($decrypted_string, strlen($this->encSalt));
			} catch(Exception $ex) {
				return false;
			}
		} else {
			return false;
		}
	}	
	/**
	 * It is a two way encryption. The result is htlml/link friendly.
	 * @param $data
	 * @return string
	 */
	public function encrypt($data)
	{
		if (!$this->encEnabled) return $data; // no encryption
		if ($this->iv) {
			$iv = openssl_random_pseudo_bytes(openssl_cipher_iv_length($this->encMethod));
		} else {
			$iv=substr(md5($data,true),0,openssl_cipher_iv_length($this->encMethod));
		}
		$encrypted_string = bin2hex($iv) . openssl_encrypt($this->encSalt . $data, $this->encMethod, $this->encPassword, 0, $iv);
		return str_replace(array('+', '/'), array('-', '_'),base64_encode($encrypted_string));
	}

	/**
	 * @param $password
	 * @param $salt
	 * @param $encMethod
	 * @throws Exception
	 */
	public function setEncryption($password, $salt, $encMethod)
	{
		if (!extension_loaded('openssl')) {
			$this->encEnabled = false;
			throw new Exception("OpenSSL not loaded, encryption disabled");
		} else {
			$this->encEnabled = true;
			$this->encPassword = $password;
			$this->encSalt = $salt;
			$this->encMethod = $encMethod;
		}
	}
}