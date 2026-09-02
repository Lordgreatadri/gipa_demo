<?php

namespace App\Services;

use RuntimeException;

class CertificateSigner
{
    public function sign(string $payload): array
    {
        $keyId = (string) config('iomp.certificates.active_key_id');
        $algorithm = (string) config('iomp.certificates.algorithm');
        if ($algorithm !== 'RSA-SHA256') {
            throw new RuntimeException("Certificate signature algorithm [{$algorithm}] is not supported.");
        }
        $privateKey = $this->privateKey($keyId);

        if (! openssl_sign($payload, $signature, $privateKey, OPENSSL_ALGO_SHA256)) {
            throw new RuntimeException('Certificate signing failed.');
        }

        return [
            'key_id' => $keyId,
            'algorithm' => $algorithm,
            'signature' => base64_encode($signature),
        ];
    }

    public function verify(string $payload, string $signature, string $keyId, string $algorithm): bool
    {
        if ($algorithm !== 'RSA-SHA256') {
            return false;
        }

        $decoded = base64_decode($signature, true);
        if ($decoded === false) {
            return false;
        }

        return openssl_verify($payload, $decoded, $this->publicKey($keyId), OPENSSL_ALGO_SHA256) === 1;
    }

    private function privateKey(string $keyId): \OpenSSLAsymmetricKey
    {
        $path = config("iomp.certificates.keys.{$keyId}.private_key_path");
        $contents = $this->readKey($path, 'private', $keyId);
        $key = openssl_pkey_get_private($contents);

        if ($key === false) {
            throw new RuntimeException("Certificate private key [{$keyId}] is invalid.");
        }

        return $key;
    }

    private function publicKey(string $keyId): \OpenSSLAsymmetricKey
    {
        $path = config("iomp.certificates.keys.{$keyId}.public_key_path");
        $contents = $this->readKey($path, 'public', $keyId);
        $key = openssl_pkey_get_public($contents);

        if ($key === false) {
            throw new RuntimeException("Certificate public key [{$keyId}] is invalid.");
        }

        return $key;
    }

    private function readKey(?string $path, string $kind, string $keyId): string
    {
        $path = $this->resolvePath($path);
        if (! $path || ! is_readable($path)) {
            throw new RuntimeException("Certificate {$kind} key [{$keyId}] is unavailable.");
        }

        $contents = file_get_contents($path);
        if ($contents === false) {
            throw new RuntimeException("Certificate {$kind} key [{$keyId}] could not be read.");
        }

        return $contents;
    }

    private function resolvePath(?string $path): ?string
    {
        if (! $path || str_starts_with($path, '/') || str_starts_with($path, '\\') || preg_match('/^[A-Za-z]:[\\\\\/]/', $path)) {
            return $path;
        }

        return storage_path('app/private/'.str_replace(['\\', '/'], DIRECTORY_SEPARATOR, ltrim($path, '\\/')));
    }
}
