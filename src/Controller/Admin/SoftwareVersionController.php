<?php

namespace App\Controller\Admin;

use App\Entity\SoftwareVersion;
use App\Form\SoftwareVersionType;
use App\Repository\SoftwareVersionRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

/**
 * Admin controller for managing firmware software versions.
 *
 * Provides CRUD operations for software version entries:
 *   - List all versions grouped by product name
 *   - Create new software version entries
 *   - Edit existing software version entries
 *   - Delete software version entries
 *
 * All routes are prefixed with /admin/software-versions and require ROLE_ADMIN.
 * Authentication is handled via Symfony form_login with database-backed users.
 */
#[Route('/admin/software-versions')]
class SoftwareVersionController extends AbstractController
{
    /**
     * List all software versions, grouped by product name.
     *
     * Displays a dashboard showing every firmware version organized into
     * collapsible sections per product (e.g., "MMI Prime CIC", "LCI MMI PRO EVO").
     * Each section shows version details with edit/delete actions.
     */
    #[Route('/', name: 'admin_software_versions_index')]
    public function index(SoftwareVersionRepository $repository): Response
    {
        $grouped = $repository->findAllGroupedByName();

        return $this->render('admin/software_version/index.html.twig', [
            'grouped_versions' => $grouped,
        ]);
    }

    /**
     * Create a new software version entry.
     *
     * Renders a form with:
     *   - Product name dropdown (predefined options to prevent typos)
     *   - Version string fields
     *   - Download link fields
     *   - "Is latest" checkbox
     *
     * When "is latest" is checked, any previous "latest" entry for the
     * same product name is automatically unmarked.
     */
    #[Route('/new', name: 'admin_software_versions_new')]
    public function new(
        Request $request,
        EntityManagerInterface $em,
        SoftwareVersionRepository $repository
    ): Response {
        $version = new SoftwareVersion();
        $form = $this->createForm(SoftwareVersionType::class, $version);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // If this version is marked as latest, unmark any previous latest
            // for the same product to maintain the one-latest-per-product rule
            if ($version->isLatest()) {
                $repository->unmarkLatestForProduct($version->getName());
            }

            $em->persist($version);
            $em->flush();

            $this->addFlash('success', sprintf(
                'Software version "%s" for %s created successfully.',
                $version->getSystemVersion(),
                $version->getName()
            ));

            return $this->redirectToRoute('admin_software_versions_index');
        }

        return $this->render('admin/software_version/form.html.twig', [
            'form'    => $form,
            'version' => $version,
            'is_new'  => true,
        ]);
    }

    /**
     * Edit an existing software version entry.
     *
     * Uses Symfony's ParamConverter to automatically fetch the entity by ID.
     * The same form and validation rules as the "new" action apply.
     */
    #[Route('/{id}/edit', name: 'admin_software_versions_edit')]
    public function edit(
        SoftwareVersion $version,
        Request $request,
        EntityManagerInterface $em,
        SoftwareVersionRepository $repository
    ): Response {
        $form = $this->createForm(SoftwareVersionType::class, $version);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // If marking as latest, unmark previous latest for this product
            if ($version->isLatest()) {
                // Snapshot all form values before the DQL bulk update,
                // because we need to refresh the entity afterwards and
                // refresh() would overwrite the user's edits.
                $formData = [
                    'name'             => $version->getName(),
                    'systemVersion'    => $version->getSystemVersion(),
                    'systemVersionAlt' => $version->getSystemVersionAlt(),
                    'link'             => $version->getLink(),
                    'stLink'           => $version->getStLink(),
                    'gdLink'           => $version->getGdLink(),
                ];

                $repository->unmarkLatestForProduct($formData['name']);

                // Refresh syncs in-memory state with DB (where isLatest is now false).
                // Without this, setIsLatest(true) is a no-op to Doctrine's change tracker.
                $em->refresh($version);

                // Re-apply all form values that refresh() just overwrote
                $version->setName($formData['name']);
                $version->setSystemVersion($formData['systemVersion']);
                $version->setSystemVersionAlt($formData['systemVersionAlt']);
                $version->setLink($formData['link']);
                $version->setStLink($formData['stLink']);
                $version->setGdLink($formData['gdLink']);
                $version->setIsLatest(true);
            }

            $version->setUpdatedAt(new \DateTimeImmutable());
            $em->flush();

            $this->addFlash('success', sprintf(
                'Software version "%s" for %s updated successfully.',
                $version->getSystemVersion(),
                $version->getName()
            ));

            return $this->redirectToRoute('admin_software_versions_index');
        }

        return $this->render('admin/software_version/form.html.twig', [
            'form'    => $form,
            'version' => $version,
            'is_new'  => false,
        ]);
    }

    /**
     * Delete a software version entry.
     *
     * Requires a valid CSRF token to prevent cross-site request forgery.
     * Only accepts POST requests for safety.
     */
    #[Route('/{id}/delete', name: 'admin_software_versions_delete', methods: ['POST'])]
    public function delete(
        SoftwareVersion $version,
        Request $request,
        EntityManagerInterface $em,
        SoftwareVersionRepository $repository
    ): Response {
        // Verify CSRF token to prevent unauthorized deletions
        $token = $request->request->get('_token');
        if ($this->isCsrfTokenValid('delete' . $version->getId(), $token)) {
            $name = $version->getName();
            $sysVersion = $version->getSystemVersion();
            $wasLatest = $version->isLatest();

            $em->remove($version);
            $em->flush();

            // If latest was deleted, auto-promote the newest remaining version.
            if ($wasLatest) {
                $replacement = $repository->findMostRecentByProduct($name);
                if ($replacement !== null) {
                    $replacement->setIsLatest(true);
                    $replacement->setUpdatedAt(new \DateTimeImmutable());
                    $em->flush();
                }
            }

            $this->addFlash('success', sprintf(
                'Software version "%s" for %s has been deleted.',
                $sysVersion,
                $name
            ));
        } else {
            $this->addFlash('danger', 'Invalid security token. Deletion was not performed.');
        }

        return $this->redirectToRoute('admin_software_versions_index');
    }
}
