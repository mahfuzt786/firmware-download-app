<?php

namespace App\Controller;

use App\Repository\SoftwareVersionRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;

/**
 * API controller for firmware version checking.
 *
 * Replicates the exact behavior of the original ConnectedSiteController::softwareDownload()
 * method. Customers submit their current software version and hardware version (HW version),
 * and the API returns:
 *   - Download links if an update is available
 *   - "Up to date" message if they have the latest version
 *   - Error message if the version/hardware cannot be identified
 *
 * All responses use HTTP 200 status code (matching the original behavior).
 *
 * HW Version Pattern Reference:
 *   Standard hardware:
 *     - ST (Standard): CPAA_XXXX.XX.XX (optional _SUFFIX)
 *     - GD (Gold):     CPAA_G_XXXX.XX.XX (optional _SUFFIX)
 *   LCI hardware:
 *     - CIC: B_C_XXXX.XX.XX
 *     - NBT: B_N_G_XXXX.XX.XX
 *     - EVO: B_E_G_XXXX.XX.XX
 */
class ApiController extends AbstractController
{
    // =========================================================================
    // HW Version Regex Patterns
    // =========================================================================

    /** Pattern for standard ST (Standard) hardware versions */
    private const PATTERN_ST = '/^CPAA_[0-9]{4}\.[0-9]{2}\.[0-9]{2}(_[A-Z]+)?$/i';

    /** Pattern for standard GD (Gold) hardware versions */
    private const PATTERN_GD = '/^CPAA_G_[0-9]{4}\.[0-9]{2}\.[0-9]{2}(_[A-Z]+)?$/i';

    /** Pattern for LCI CIC hardware versions */
    private const PATTERN_LCI_CIC = '/^B_C_[0-9]{4}\.[0-9]{2}\.[0-9]{2}$/i';

    /** Pattern for LCI NBT hardware versions */
    private const PATTERN_LCI_NBT = '/^B_N_G_[0-9]{4}\.[0-9]{2}\.[0-9]{2}$/i';

    /** Pattern for LCI EVO hardware versions */
    private const PATTERN_LCI_EVO = '/^B_E_G_[0-9]{4}\.[0-9]{2}\.[0-9]{2}$/i';

    /**
     * Check a customer's software version and return the appropriate response.
     *
     * Request body (POST):
     *   - version:    (required) Customer's current software version
     *   - mcuVersion: (optional) MCU version - currently not used in matching
     *   - hwVersion:  (required) Customer's hardware version string
     *
     * Response format (JSON, always HTTP 200):
     *   Success (version found, update available):
     *     { "versionExist": true, "msg": "The latest version...", "link": "...", "st": "...", "gd": "..." }
     *   Success (version found, already latest):
     *     { "versionExist": true, "msg": "Your system is upto date!", "link": "", "st": "", "gd": "" }
     *   Error (version not found or HW invalid):
     *     { "versionExist": false, "msg": "There was a problem...", "link": "", "st": "", "gd": "" }
     *   Validation error:
     *     { "msg": "Version is required" } or { "msg": "HW Version is required" }
     */
    #[Route('/api/software/version', name: 'api_software_version', methods: ['POST'])]
    public function softwareDownload(
        Request $request,
        SoftwareVersionRepository $repository
    ): JsonResponse {
        // ---------------------------------------------------------------------
        // Step 1: Extract and validate request parameters
        // ---------------------------------------------------------------------
        $version = $request->request->get('version');
        $hwVersion = $request->request->get('hwVersion');

        if (empty($version)) {
            return new JsonResponse(['msg' => 'Version is required'], 200);
        }

        if (empty($hwVersion)) {
            return new JsonResponse(['msg' => 'HW Version is required'], 200);
        }

        // ---------------------------------------------------------------------
        // Step 2: Determine hardware type from HW version pattern
        // ---------------------------------------------------------------------
        // The HW version string identifies the hardware variant. We match it
        // against known patterns to determine:
        //   - Whether it's a valid/recognized hardware ($hwVersionBool)
        //   - Whether to show ST download links ($stBool)
        //   - Whether to show GD download links ($gdBool)
        //   - Whether it's an LCI variant ($isLCI)
        //   - For LCI, the specific hardware type CIC/NBT/EVO ($lciHwType)
        // ---------------------------------------------------------------------
        $hwVersionBool = false;
        $stBool = false;
        $gdBool = false;
        $isLCI = false;
        $lciHwType = '';

        // Check standard hardware patterns
        if (preg_match(self::PATTERN_ST, $hwVersion)) {
            $hwVersionBool = true;
            $stBool = true;
        }

        if (preg_match(self::PATTERN_GD, $hwVersion)) {
            $hwVersionBool = true;
            $gdBool = true;
        }

        // Check LCI hardware patterns (these override standard flags)
        if (preg_match(self::PATTERN_LCI_CIC, $hwVersion)) {
            $hwVersionBool = true;
            $isLCI = true;
            $lciHwType = 'CIC';
            $stBool = true;       // LCI CIC uses ST links
        } elseif (preg_match(self::PATTERN_LCI_NBT, $hwVersion)) {
            $hwVersionBool = true;
            $isLCI = true;
            $lciHwType = 'NBT';
            $gdBool = true;        // LCI NBT uses GD links
        } elseif (preg_match(self::PATTERN_LCI_EVO, $hwVersion)) {
            $hwVersionBool = true;
            $isLCI = true;
            $lciHwType = 'EVO';
            $gdBool = true;        // LCI EVO uses GD links
        }

        // If the HW version doesn't match any known pattern, return an error
        if (!$hwVersionBool) {
            return new JsonResponse([
                'msg' => 'There was a problem identifying your software. Contact us for help.'
            ], 200);
        }

        // ---------------------------------------------------------------------
        // Step 3: Normalize the software version input
        // ---------------------------------------------------------------------
        // Strip leading 'v' or 'V' prefix since the database stores the
        // alternative version without it
        if (str_starts_with(strtolower($version), 'v')) {
            $version = substr($version, 1);
        }

        // ---------------------------------------------------------------------
        // Step 4: Look up the version in the database
        // ---------------------------------------------------------------------
        $matches = $repository->findByVersionAlt($version);

        $response = [];
        foreach ($matches as $row) {
            // Check if this entry belongs to the correct hardware family
            $isLCIEntry = str_starts_with($row->getName(), 'LCI');

            // Standard HW must only match standard entries; LCI must only match LCI
            if ($isLCI !== $isLCIEntry) {
                continue;
            }

            // For LCI hardware, also verify the specific type (CIC/NBT/EVO)
            // matches the entry's product name
            if ($isLCI && stripos($row->getName(), $lciHwType) === false) {
                continue;
            }

            // -----------------------------------------------------------------
            // Step 5: Build the response based on whether this is the latest
            // -----------------------------------------------------------------
            if ($row->isLatest()) {
                // Customer already has the latest version
                $response = [
                    'versionExist' => true,
                    'msg'          => 'Your system is upto date!',
                    'link'         => '',
                    'st'           => '',
                    'gd'           => '',
                ];
            } else {
                // Update available - include the appropriate download links
                $stLink = $stBool ? ($row->getStLink() ?? '') : '';
                $gdLink = $gdBool ? ($row->getGdLink() ?? '') : '';

                // Dynamically determine the latest version label for the message
                $latestVersion = $repository->findLatestInFamily($isLCI);
                $latestMsg = $latestVersion
                    ? $latestVersion->getDisplayVersion()
                    : ($isLCI ? 'v3.4.4' : 'v3.3.7'); // Fallback values

                $response = [
                    'versionExist' => true,
                    'msg'          => 'The latest version of software is ' . $latestMsg . ' ',
                    'link'         => $row->getLink() ?? '',
                    'st'           => $stLink,
                    'gd'           => $gdLink,
                ];
            }

            // Use the first matching entry only
            break;
        }

        // Return the match result if found
        if ($response) {
            return new JsonResponse($response, 200);
        }

        // ---------------------------------------------------------------------
        // Step 6: No matching version found
        // ---------------------------------------------------------------------
        return new JsonResponse([
            'versionExist' => false,
            'msg'          => 'There was a problem identifying your software. Contact us for help.',
            'link'         => '',
            'st'           => '',
            'gd'           => '',
        ], 200);
    }
}
