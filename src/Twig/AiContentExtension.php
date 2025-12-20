<?php

declare(strict_types=1);

namespace Xmon\AiContentBundle\Twig;

use Twig\Extension\AbstractExtension;
use Twig\Extension\GlobalsInterface;

/**
 * Twig extension that exposes bundle configuration as global variables.
 */
class AiContentExtension extends AbstractExtension implements GlobalsInterface
{
    public function __construct(
        private readonly string $adminBaseTemplate,
    ) {
    }

    public function getGlobals(): array
    {
        return [
            'xmon_ai_base_template' => $this->adminBaseTemplate,
        ];
    }
}
