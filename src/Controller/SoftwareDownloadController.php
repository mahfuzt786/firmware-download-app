<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

/**
 * Controller for the public-facing firmware download page.
 *
 * This renders the customer-facing form where users enter their current
 * software version and hardware version to check for firmware updates.
 * The form submits via AJAX to the API endpoint (/api/software/version).
 */
class SoftwareDownloadController extends AbstractController
{
    /**
     * Render the firmware download check page.
     * Accessible at: /software-download
     */
    #[Route('/', name: 'software_download')]
    public function index(): Response
    {
        return $this->render('software_download/index.html.twig');
    }
}
