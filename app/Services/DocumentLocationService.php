<?php

namespace App\Services;

use App\Models\DocumentLocation;
use App\Models\DocumentLocalConfig;
use App\Models\DocumentWasabiConfig;
use App\Models\DocumentAwsConfig;
use Exception;
use Illuminate\Database\Eloquent\Collection;

class DocumentLocationService
{
    public function getAllLocations(): Collection
    {
        return DocumentLocation::with(['localConfig', 'wasabiConfig', 'awsConfig'])->get();
    }

    public function getLocation(int $id): ?DocumentLocation
    {
        return DocumentLocation::with(['localConfig', 'wasabiConfig', 'awsConfig'])->find($id);
    }

    public function updateLocation(int $id, array $data): DocumentLocation
    {
        $location = DocumentLocation::findOrFail($id);
        $location->update([
            'is_active' => $data['is_active'] ?? $location->is_active,
        ]);
        return $location;
    }

    /**
     * Update/Create configuration based on location type
     */
    public function updateConfiguration(int $locationId, array $data): mixed
    {
        $location = DocumentLocation::findOrFail($locationId);

        if ($location->slug === 'local') {
            return DocumentLocalConfig::updateOrCreate(
                ['location_id' => $location->id],
                [
                    'root_path' => $data['root_path'] ?? 'storage/documents',
                    'is_active' => $data['is_active'] ?? true,
                ]
            );
        }

        if ($location->slug === 'wasabi') {
            return DocumentWasabiConfig::updateOrCreate(
                ['location_id' => $location->id],
                [
                    'bucket' => $data['bucket'],
                    'region' => $data['region'],
                    'access_key' => $data['access_key'],
                    'secret_key' => $data['secret_key'],
                    'endpoint' => $data['endpoint'],
                    'is_active' => $data['is_active'] ?? true,
                ]
            );
        }

        if ($location->slug === 'aws') {
            return DocumentAwsConfig::updateOrCreate(
                ['location_id' => $location->id],
                [
                    'bucket' => $data['bucket'],
                    'region' => $data['region'],
                    'access_key' => $data['access_key'],
                    'secret_key' => $data['secret_key'],
                    'is_active' => $data['is_active'] ?? true,
                ]
            );
        }

        throw new Exception("Unknown location type: " . $location->slug);
    }
}
