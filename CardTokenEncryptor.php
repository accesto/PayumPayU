<?php

namespace Accesto\Component\Payum\PayU;

/**
 * Class CardTokenEncryptor.
 */
class CardTokenEncryptor
{
    private static $cihpher = "aes-128-cbc";

    public static function encrypt($token, $key)
    {
        $salt = openssl_random_pseudo_bytes(openssl_cipher_iv_length(self::$cihpher));

        return [base64_encode(openssl_encrypt($token, self::$cihpher, $key, OPENSSL_RAW_DATA, $salt)), base64_encode($salt)];
    }

    public static function decrypt($encryptedToken, $key, $salt)
    {
        return openssl_decrypt(base64_decode($encryptedToken), self::$cihpher, $key, OPENSSL_RAW_DATA, base64_decode($salt));
    }
}
