<?php

namespace App;

use Symfony\Bundle\FrameworkBundle\Kernel\MicroKernelTrait;
use Symfony\Component\HttpKernel\Kernel as BaseKernel;

/**
 * Application kernel for the BimmerTech Firmware Download app.
 * Uses the MicroKernelTrait for simplified configuration.
 */
class Kernel extends BaseKernel
{
    use MicroKernelTrait;
}
