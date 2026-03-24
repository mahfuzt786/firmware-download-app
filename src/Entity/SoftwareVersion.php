<?php

namespace App\Entity;

use App\Repository\SoftwareVersionRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Entity representing a firmware/software version for BimmerTech CarPlay/Android Auto MMI.
 *
 * Each record corresponds to a specific software version for a particular hardware variant
 * (e.g., "MMI Prime CIC", "LCI MMI PRO EVO"). The version includes download links for
 * different hardware sub-types (ST = Standard, GD = Gold).
 *
 * Product families:
 *   - Standard hardware: "MMI Prime CIC/NBT/EVO", "MMI PRO CIC/NBT/EVO"
 *   - LCI hardware:      "LCI MMI Prime CIC/NBT/EVO", "LCI MMI PRO CIC/NBT/EVO"
 *
 * Hardware sub-types and their download links:
 *   - CIC products use ST links only
 *   - NBT products use both ST and GD links (standard) or GD only (LCI)
 *   - EVO products use both ST and GD links (standard) or GD only (LCI)
 */
#[ORM\Entity(repositoryClass: SoftwareVersionRepository::class)]
#[ORM\Table(name: 'software_versions')]
#[ORM\Index(columns: ['name'], name: 'idx_name')]
#[ORM\Index(columns: ['is_latest'], name: 'idx_is_latest')]
#[ORM\Index(columns: ['system_version_alt'], name: 'idx_system_version_alt')]
class SoftwareVersion
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    /**
     * Product name / hardware variant identifier.
     * Must match one of the predefined product names exactly.
     * Examples: "MMI Prime CIC", "MMI PRO NBT", "LCI MMI Prime EVO"
     */
    #[ORM\Column(length: 100)]
    #[Assert\NotBlank(message: 'Product name is required.')]
    private ?string $name = null;

    /**
     * Full system version string including the 'v' prefix.
     * This is the canonical version identifier stored in the firmware.
     * Example: "v3.3.7.mmipri.c"
     */
    #[ORM\Column(length: 100)]
    #[Assert\NotBlank(message: 'System version is required.')]
    private ?string $systemVersion = null;

    /**
     * Alternative system version string without the 'v' prefix.
     * This is the value matched against customer input during version lookup.
     * Example: "3.3.7.mmipri.c"
     */
    #[ORM\Column(length: 100)]
    #[Assert\NotBlank(message: 'System version (alt) is required.')]
    private ?string $systemVersionAlt = null;

    /**
     * General download folder link (typically a Google Drive URL).
     * Contains the complete firmware package. May be empty for LCI versions.
     */
    #[ORM\Column(length: 500, nullable: true)]
    private ?string $link = null;

    /**
     * Download link for ST (Standard) hardware variant firmware.
     * Applicable for CIC products and some standard NBT/EVO products.
     * Leave empty if this version has no ST firmware.
     */
    #[ORM\Column(name: 'st_link', length: 500, nullable: true)]
    private ?string $stLink = null;

    /**
     * Download link for GD (Gold) hardware variant firmware.
     * Applicable for NBT and EVO products.
     * Leave empty if this version has no GD firmware.
     */
    #[ORM\Column(name: 'gd_link', length: 500, nullable: true)]
    private ?string $gdLink = null;

    /**
     * Flag indicating whether this is the latest (most current) version
     * for the given product name. Only ONE version per product name
     * should be marked as latest at any time.
     *
     * When a user's version matches a "latest" entry, they see
     * "Your system is upto date!" instead of a download link.
     */
    #[ORM\Column]
    private bool $isLatest = false;

    /** Timestamp when this record was created. */
    #[ORM\Column]
    private ?\DateTimeImmutable $createdAt = null;

    /** Timestamp when this record was last updated. */
    #[ORM\Column]
    private ?\DateTimeImmutable $updatedAt = null;

    public function __construct()
    {
        $this->createdAt = new \DateTimeImmutable();
        $this->updatedAt = new \DateTimeImmutable();
    }

    // =========================================================================
    // Getters and Setters
    // =========================================================================

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(string $name): static
    {
        $this->name = $name;
        return $this;
    }

    public function getSystemVersion(): ?string
    {
        return $this->systemVersion;
    }

    public function setSystemVersion(string $systemVersion): static
    {
        $this->systemVersion = $systemVersion;
        return $this;
    }

    public function getSystemVersionAlt(): ?string
    {
        return $this->systemVersionAlt;
    }

    public function setSystemVersionAlt(string $systemVersionAlt): static
    {
        $this->systemVersionAlt = $systemVersionAlt;
        return $this;
    }

    public function getLink(): ?string
    {
        return $this->link;
    }

    public function setLink(?string $link): static
    {
        $this->link = $link;
        return $this;
    }

    public function getStLink(): ?string
    {
        return $this->stLink;
    }

    public function setStLink(?string $stLink): static
    {
        $this->stLink = $stLink;
        return $this;
    }

    public function getGdLink(): ?string
    {
        return $this->gdLink;
    }

    public function setGdLink(?string $gdLink): static
    {
        $this->gdLink = $gdLink;
        return $this;
    }

    public function isLatest(): bool
    {
        return $this->isLatest;
    }

    public function setIsLatest(bool $isLatest): static
    {
        $this->isLatest = $isLatest;
        return $this;
    }

    public function getCreatedAt(): ?\DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function setCreatedAt(\DateTimeImmutable $createdAt): static
    {
        $this->createdAt = $createdAt;
        return $this;
    }

    public function getUpdatedAt(): ?\DateTimeImmutable
    {
        return $this->updatedAt;
    }

    public function setUpdatedAt(\DateTimeImmutable $updatedAt): static
    {
        $this->updatedAt = $updatedAt;
        return $this;
    }

    // =========================================================================
    // Helper Methods
    // =========================================================================

    /**
     * Determine if this version belongs to an LCI product.
     * LCI entries have names starting with "LCI".
     */
    public function isLCI(): bool
    {
        return str_starts_with($this->name ?? '', 'LCI');
    }

    /**
     * Extract a short, user-friendly display version from the full system version.
     * For example: "v3.3.7.mmipri.c" => "v3.3.7"
     *
     * The algorithm takes all leading dot-separated segments that start with
     * a digit, producing the numeric-only portion of the version string.
     */
    public function getDisplayVersion(): string
    {
        $version = ltrim($this->systemVersion ?? '', 'vV');
        $parts = explode('.', $version);
        $versionParts = [];

        foreach ($parts as $part) {
            // Keep segments that start with a digit (e.g., "3", "1", "7R4")
            if (preg_match('/^[0-9]/', $part)) {
                $versionParts[] = $part;
            } else {
                break;
            }
        }

        return 'v' . implode('.', $versionParts);
    }
}
