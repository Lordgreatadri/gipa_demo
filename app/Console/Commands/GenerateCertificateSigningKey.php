<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

class GenerateCertificateSigningKey extends Command
{
    protected $signature = 'certificates:key-generate {--force : Replace an existing local key pair}';

    protected $description = 'Generate the local RSA signing key pair used by the certificate MVP';

    public function handle(): int
    {
        $keyId = (string) config('iomp.certificates.active_key_id');
        $privatePath = $this->resolvePath((string) config("iomp.certificates.keys.{$keyId}.private_key_path"));
        $publicPath = $this->resolvePath((string) config("iomp.certificates.keys.{$keyId}.public_key_path"));
        if (! $privatePath || ! $publicPath) {
            $this->error("Certificate key paths are not configured for [{$keyId}].");

            return self::FAILURE;
        }
        if (! $this->option('force') && (File::exists($privatePath) || File::exists($publicPath))) {
            $this->error('A certificate key already exists. Use --force only for deliberate replacement or rotation.');

            return self::FAILURE;
        }

        $opensslConfig = dirname(PHP_BINARY).DIRECTORY_SEPARATOR.'extras'.DIRECTORY_SEPARATOR.'ssl'.DIRECTORY_SEPARATOR.'openssl.cnf';
        $options = array_filter([
            'config' => File::isFile($opensslConfig) ? $opensslConfig : null,
            'private_key_type' => OPENSSL_KEYTYPE_RSA,
            'private_key_bits' => 3072,
        ]);
        $key = openssl_pkey_new($options);
        if ($key === false || ! openssl_pkey_export($key, $privatePem, null, $options)) {
            $this->error('OpenSSL could not generate the certificate signing key. Verify the OpenSSL provider configuration.');

            return self::FAILURE;
        }
        $details = openssl_pkey_get_details($key);
        if ($details === false) {
            $this->error('OpenSSL could not export the certificate public key.');

            return self::FAILURE;
        }

        File::ensureDirectoryExists(dirname($privatePath), 0700);
        File::ensureDirectoryExists(dirname($publicPath), 0700);
        File::put($privatePath, $privatePem);
        File::put($publicPath, $details['key']);
        @chmod($privatePath, 0600);
        @chmod($publicPath, 0644);

        $this->info("Generated certificate signing key [{$keyId}].");
        $this->line("Private key: {$privatePath}");
        $this->line("Public key: {$publicPath}");
        $this->warn('Back up and restrict the private key. Use an approved key-management service for production.');

        return self::SUCCESS;
    }

    private function resolvePath(string $path): string
    {
        if (! $path || str_starts_with($path, '/') || str_starts_with($path, '\\') || preg_match('/^[A-Za-z]:[\\\\\/]/', $path)) {
            return $path;
        }

        return storage_path('app/private/'.str_replace(['\\', '/'], DIRECTORY_SEPARATOR, ltrim($path, '\\/')));
    }
}
