<?php
declare(strict_types=1);

namespace app\common;

/**
 * 微信公众平台消息体加解密（兼容/安全模式）
 * @see https://developers.weixin.qq.com/doc/offaccount/Message_Management/Message_encryption_and_decryption_instructions.html
 */
final class WechatOfficialMsgCrypt
{
    public static function verifyPlainSignature(string $token, string $signature, string $timestamp, string $nonce): bool
    {
        if ($signature === '' || $timestamp === '' || $nonce === '') {
            return false;
        }
        $arr = [$token, $timestamp, $nonce];
        sort($arr, SORT_STRING);
        $hash = sha1(implode('', $arr));
        return hash_equals($hash, $signature);
    }

    /**
     * 密文模式：sha1(sort(token, timestamp, nonce, encrypt))
     */
    public static function verifyEncryptSignature(string $token, string $timestamp, string $nonce, string $encrypt, string $msgSignature): bool
    {
        if ($msgSignature === '' || $timestamp === '' || $nonce === '' || $encrypt === '') {
            return false;
        }
        $arr = [$token, $timestamp, $nonce, $encrypt];
        sort($arr, SORT_STRING);
        $hash = sha1(implode('', $arr));
        return hash_equals($hash, $msgSignature);
    }

    /**
     * @return string|false 解密后的明文（通常为 XML 字符串）
     */
    public static function decrypt(string $encodingAesKey, string $appId, string $encryptedBase64)
    {
        $key = base64_decode($encodingAesKey . '=', true);
        if ($key === false || strlen($key) !== 32) {
            return false;
        }
        $iv = substr($key, 0, 16);
        $ciphertext = base64_decode($encryptedBase64, true);
        if ($ciphertext === false || $ciphertext === '') {
            return false;
        }
        $plain = openssl_decrypt($ciphertext, 'AES-256-CBC', $key, OPENSSL_RAW_DATA | OPENSSL_NO_PADDING, $iv);
        if ($plain === false || $plain === '') {
            return false;
        }
        $pad = ord($plain[strlen($plain) - 1]);
        if ($pad < 1 || $pad > 32) {
            return false;
        }
        if (strlen($plain) < $pad) {
            return false;
        }
        $plain = substr($plain, 0, -$pad);
        if (strlen($plain) < 20) {
            return false;
        }
        $content = substr($plain, 16);
        $unpack = unpack('Nlen', substr($content, 0, 4));
        $xmlLen = (int)($unpack['len'] ?? 0);
        if ($xmlLen <= 0 || strlen($content) < 4 + $xmlLen) {
            return false;
        }
        $xml = substr($content, 4, $xmlLen);
        $fromAppId = substr($content, 4 + $xmlLen);
        if ($fromAppId !== $appId) {
            return false;
        }
        return $xml;
    }
}
