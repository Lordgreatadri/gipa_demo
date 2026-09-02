<?php

namespace Tests\Support;

trait CreatesCertificateSigningKeys
{
    private function configureCertificateSigningKey(string $keyId, bool $keepExisting = false): void
    {
        $baseOptions = [
            'private_key_type' => OPENSSL_KEYTYPE_RSA,
            'private_key_bits' => 2048,
        ];
        $options = $baseOptions;
        $environmentConfig = getenv('OPENSSL_CONF');

        if (is_string($environmentConfig) && is_readable($environmentConfig)) {
            $options['config'] = $environmentConfig;
        }

        $key = openssl_pkey_new($options);
        if ($key === false && isset($options['config'])) {
            $options = $baseOptions;
            $key = openssl_pkey_new($options);
        }

        if ($key === false) {
            $bundledConfig = dirname(PHP_BINARY).DIRECTORY_SEPARATOR.'extras'.DIRECTORY_SEPARATOR.'ssl'.DIRECTORY_SEPARATOR.'openssl.cnf';
            if (is_readable($bundledConfig)) {
                $options = $baseOptions + ['config' => $bundledConfig];
                $key = openssl_pkey_new($options);
            }
        }

        $this->assertNotFalse($key, 'Ephemeral OpenSSL key generation failed using the environment and platform defaults.');
        $this->assertTrue(openssl_pkey_export($key, $privatePem, null, $options));
        $details = openssl_pkey_get_details($key);
        $this->assertNotFalse($details, 'Ephemeral public key export failed.');
        $privatePath = tempnam(sys_get_temp_dir(), 'iomp-private-');
        $publicPath = tempnam(sys_get_temp_dir(), 'iomp-public-');
        $this->assertNotFalse($privatePath);
        $this->assertNotFalse($publicPath);
        file_put_contents($privatePath, $privatePem);
        file_put_contents($publicPath, $details['key']);
        $this->keyFiles = [...$this->keyFiles, $privatePath, $publicPath];

        $keys = $keepExisting ? config('iomp.certificates.keys', []) : [];
        $keys[$keyId] = ['private_key_path' => $privatePath, 'public_key_path' => $publicPath];
        config()->set('iomp.certificates.keys', $keys);
        config()->set('iomp.certificates.active_key_id', $keyId);
        config()->set('iomp.certificates.algorithm', 'RSA-SHA256');
    }
}
