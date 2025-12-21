<?php

declare(strict_types=1);

namespace Xmon\AiContentBundle\Tests;

use PHPUnit\Framework\TestCase;
use Xmon\AiContentBundle\XmonAiContentBundle;

/**
 * Basic test to verify the bundle loads correctly.
 */
class XmonAiContentBundleTest extends TestCase
{
    public function testBundleCanBeInstantiated(): void
    {
        $bundle = new XmonAiContentBundle();

        $this->assertInstanceOf(XmonAiContentBundle::class, $bundle);
    }

    public function testBundleHasCorrectName(): void
    {
        $bundle = new XmonAiContentBundle();

        $this->assertSame('XmonAiContentBundle', $bundle->getName());
    }
}
