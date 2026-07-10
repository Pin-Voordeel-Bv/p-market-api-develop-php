<?php
declare(strict_types=1);
namespace PinVandaag\PMarketAPIDevelop\Support;
use PinVandaag\PMarketAPIDevelop\Exception\PMarketAPIDevelopException;
final class AesEncryptor
{
    public static function encrypt(string $plainText, string $apiSecret): string
    {
        $key = md5($apiSecret, true);
        $encrypted = openssl_encrypt($plainText, 'AES-128-ECB', $key, OPENSSL_RAW_DATA);
        if ($encrypted === false) throw new PMarketAPIDevelopException('Could not encrypt app key/secret.');
        return base64_encode($encrypted);
    }
}
