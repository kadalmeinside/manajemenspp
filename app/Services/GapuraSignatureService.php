<?php

namespace App\Services;

class GapuraSignatureService
{
    /**
     * Menghapus spasi dan format tambahan dari JSON payload.
     */
    public static function minifyBody(array|string $body): string
    {
        if (is_array($body)) {
            return json_encode($body, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
        }

        if (is_string($body) && !empty($body)) {
            $decoded = json_decode($body, true);
            if (json_last_error() === JSON_ERROR_NONE) {
                return json_encode($decoded, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
            }
        }
        
        return $body ?: '';
    }

    /**
     * Melakukan SHA-256 hashing pada minified body lalu diencode ke format Hexadesimal (huruf kecil).
     */
    public static function hashBody(string $minifiedBody): string
    {
        return strtolower(hash('sha256', $minifiedBody));
    }

    /**
     * Menyatukan string dengan format dan mengenkripsinya menggunakan RSA-256 dengan PKCS#1 Private Key.
     */
    public static function generateSignature(
        string $httpMethod,
        string $relativeUrl,
        array|string $body,
        string $timestamp,
        string $privateKeyPem
    ): string {
        $httpMethod = strtoupper($httpMethod);
        $minifiedBody = self::minifyBody($body);
        $hashedBody = self::hashBody($minifiedBody);

        // Format String to Sign:
        // <HTTP METHOD> + ":" + <RELATIVE PATH URL> + ":" + LowerCase(HexEncode(SHA-256(Minify(<HTTP BODY>)))) + ":" + <X-TIMESTAMP>
        $stringToSign = $httpMethod . ":" . $relativeUrl . ":" . $hashedBody . ":" . $timestamp;

        // Sign using RSA-2048 and SHA-256
        $signature = '';
        $privateKeyId = openssl_get_privatekey($privateKeyPem);
        
        if (!$privateKeyId) {
            throw new \Exception("Gapura Private Key tidak valid atau belum disetel di pengaturan.");
        }

        openssl_sign($stringToSign, $signature, $privateKeyId, OPENSSL_ALGO_SHA256);
        
        // Output Base64 encoded signature
        return base64_encode($signature);
    }

    /**
     * Memverifikasi signature menggunakan RSA-256 dengan Public Key dari DANA.
     */
    public static function verifySignature(
        string $httpMethod,
        string $relativeUrl,
        array|string $body,
        string $timestamp,
        string $signatureBase64,
        string $publicKeyPem
    ): bool {
        $httpMethod = strtoupper($httpMethod);
        $minifiedBody = self::minifyBody($body);
        $hashedBody = self::hashBody($minifiedBody);

        // Format String to Sign:
        // <HTTP METHOD> + ":" + <RELATIVE PATH URL> + ":" + LowerCase(HexEncode(SHA-256(Minify(<HTTP BODY>)))) + ":" + <X-TIMESTAMP>
        $stringToSign = $httpMethod . ":" . $relativeUrl . ":" . $hashedBody . ":" . $timestamp;

        $publicKeyId = openssl_get_publickey($publicKeyPem);
        
        if (!$publicKeyId) {
            throw new \Exception("DANA Public Key tidak valid atau belum disetel di pengaturan.");
        }

        $signatureRaw = base64_decode($signatureBase64);
        
        $result = openssl_verify($stringToSign, $signatureRaw, $publicKeyId, OPENSSL_ALGO_SHA256);
        
        return $result === 1;
    }
}
