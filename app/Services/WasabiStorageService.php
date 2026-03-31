<?php

namespace App\Services;

use Aws\S3\S3Client;
use Illuminate\Support\Facades\Storage;
use League\Flysystem\Filesystem;
use League\Flysystem\AwsS3V3\AwsS3V3Adapter;

class WasabiStorageService
{
    public static function make(array $config): Filesystem
    {
        $client = new S3Client([
            'credentials' => [
                'key'    => $config['access_key'],
                'secret' => $config['secret_key'],
            ],
            'region' => $config['region'],
            'version' => 'latest',
            'endpoint' => $config['endpoint'],
            'use_path_style_endpoint' => true,
        ]);

        $adapter = new AwsS3V3Adapter(
            $client,
            $config['bucket']
        );

        return new Filesystem($adapter);
    }
}
