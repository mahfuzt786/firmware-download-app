<?php

namespace App\Form;

use App\Entity\SoftwareVersion;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Form type for creating and editing SoftwareVersion entities.
 *
 * Designed for non-technical users with:
 *   - A dropdown for product names (prevents typos and ensures consistency)
 *   - Clear labels and help text for every field
 *   - Placeholder examples showing the expected format
 *
 * Product names are grouped into "Standard Hardware" and "LCI Hardware"
 * categories in the dropdown for easier navigation.
 */
class SoftwareVersionType extends AbstractType
{
    /**
     * Predefined product names grouped by hardware family.
     *
     * These are the only valid product names. Using a dropdown instead of
     * free-text ensures data consistency and prevents accidental mismatches
     * in the version-checking logic.
     */
    private const PRODUCT_NAMES = [
        'Standard Hardware' => [
            'MMI Prime CIC' => 'MMI Prime CIC',
            'MMI Prime NBT' => 'MMI Prime NBT',
            'MMI Prime EVO' => 'MMI Prime EVO',
            'MMI PRO CIC'   => 'MMI PRO CIC',
            'MMI PRO NBT'   => 'MMI PRO NBT',
            'MMI PRO EVO'   => 'MMI PRO EVO',
        ],
        'LCI Hardware' => [
            'LCI MMI Prime CIC' => 'LCI MMI Prime CIC',
            'LCI MMI Prime NBT' => 'LCI MMI Prime NBT',
            'LCI MMI Prime EVO' => 'LCI MMI Prime EVO',
            'LCI MMI PRO CIC'   => 'LCI MMI PRO CIC',
            'LCI MMI PRO NBT'   => 'LCI MMI PRO NBT',
            'LCI MMI PRO EVO'   => 'LCI MMI PRO EVO',
        ],
    ];

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            // Product name - dropdown selection to prevent typos
            ->add('name', ChoiceType::class, [
                'label'       => 'Product Name',
                'choices'     => self::PRODUCT_NAMES,
                'placeholder' => '-- Select Product --',
                'help'        => 'Select the hardware variant this firmware version belongs to.',
                'attr'        => ['class' => 'form-select'],
            ])

            // System version with 'v' prefix (canonical identifier)
            ->add('systemVersion', TextType::class, [
                'label' => 'System Version',
                'help'  => 'Full version string with "v" prefix, as stored in the firmware. Example: v3.3.7.mmipri.c',
                'attr'  => ['placeholder' => 'e.g., v3.3.7.mmipri.c'],
            ])

            // Alternative system version without 'v' prefix (used for matching)
            ->add('systemVersionAlt', TextType::class, [
                'label' => 'System Version (Alt)',
                'help'  => 'Same as above but without the "v" prefix. This is matched against customer input. Example: 3.3.7.mmipri.c',
                'attr'  => ['placeholder' => 'e.g., 3.3.7.mmipri.c'],
            ])

            // General download folder link
            ->add('link', TextType::class, [
                'label'    => 'General Download Link',
                'required' => false,
                'help'     => 'Google Drive folder URL for general downloads. Leave empty if not applicable (e.g., for LCI versions).',
                'attr'     => ['placeholder' => 'https://drive.google.com/drive/folders/...'],
            ])

            // ST firmware download link
            ->add('stLink', TextType::class, [
                'label'    => 'ST Firmware Download Link',
                'required' => false,
                'help'     => 'Download link for ST (Standard) hardware. Required for CIC products. Used when HW version matches CPAA_XXXX.XX.XX pattern.',
                'attr'     => ['placeholder' => 'https://drive.google.com/drive/folders/...'],
            ])

            // GD firmware download link
            ->add('gdLink', TextType::class, [
                'label'    => 'GD Firmware Download Link',
                'required' => false,
                'help'     => 'Download link for GD (Gold) hardware. Required for NBT and EVO products. Used when HW version matches CPAA_G_XXXX.XX.XX pattern.',
                'attr'     => ['placeholder' => 'https://drive.google.com/drive/folders/...'],
            ])

            // Latest version flag
            ->add('isLatest', CheckboxType::class, [
                'label'    => 'This is the latest version',
                'required' => false,
                'help'     => 'Check this box if this is the most current firmware release for this product. Only one version per product should be marked as latest. Previous "latest" entries will be automatically unmarked.',
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => SoftwareVersion::class,
        ]);
    }
}
