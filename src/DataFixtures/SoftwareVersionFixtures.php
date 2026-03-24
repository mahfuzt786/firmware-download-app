<?php

namespace App\DataFixtures;

use App\Entity\SoftwareVersion;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;

/**
 * Database fixtures to populate the software_versions table with all existing
 * firmware versions from the original softwareversions.json file.
 *
 * This fixture reads the JSON seed data file located at data/softwareversions.json
 * and creates a SoftwareVersion entity for each entry. Run this after creating
 * the database schema to populate it with all known firmware versions.
 *
 * Usage:
 *   php bin/console doctrine:fixtures:load
 *
 * WARNING: This will purge all existing data in the database before loading.
 * Use --append flag to add data without purging:
 *   php bin/console doctrine:fixtures:load --append
 */
class SoftwareVersionFixtures extends Fixture
{
    public function load(ObjectManager $manager): void
    {
        // Path to the seed data JSON file (relative to project root)
        $jsonPath = dirname(__DIR__, 2) . '/data/softwareversions.json';

        if (!file_exists($jsonPath)) {
            throw new \RuntimeException(
                'Seed data file not found at: ' . $jsonPath . "\n" .
                'Please ensure the data/softwareversions.json file exists in the project root.'
            );
        }

        $jsonContent = file_get_contents($jsonPath);
        $data = json_decode($jsonContent, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new \RuntimeException(
                'Failed to parse seed data JSON: ' . json_last_error_msg()
            );
        }

        $count = 0;
        foreach ($data as $entry) {
            $version = new SoftwareVersion();
            $version->setName($entry['name']);
            $version->setSystemVersion($entry['system_version']);
            $version->setSystemVersionAlt($entry['system_version_alt']);

            // Store empty strings as null for cleaner database storage
            $version->setLink(!empty($entry['link']) ? $entry['link'] : null);
            $version->setStLink(!empty($entry['st']) ? $entry['st'] : null);
            $version->setGdLink(!empty($entry['gd']) ? $entry['gd'] : null);

            $version->setIsLatest($entry['latest'] ?? false);

            $manager->persist($version);
            $count++;
        }

        $manager->flush();

        // Output count for confirmation (visible in console output)
        echo sprintf("Loaded %d software version entries.\n", $count);
    }
}
