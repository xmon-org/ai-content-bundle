<?php

declare(strict_types=1);

namespace Xmon\AiContentBundle\Tests\Service;

use PHPUnit\Framework\TestCase;
use Xmon\AiContentBundle\Service\VariantSelector;

/**
 * Tests for VariantSelector service.
 *
 * The algorithm requires keywords to appear in BOTH the content AND the option
 * to score. This means content and options should be in the same language.
 */
class VariantSelectorTest extends TestCase
{
    private VariantSelector $selector;

    protected function setUp(): void
    {
        $this->selector = new VariantSelector();
    }

    public function testSelectWithNoContent(): void
    {
        $variants = [
            'location' => ['patio de museo', 'terraza en azotea'],
        ];

        $result = $this->selector->select($variants, '');

        $this->assertArrayHasKey('location', $result);
        $this->assertContains($result['location'], $variants['location']);
    }

    public function testSelectWithKeywordMatch(): void
    {
        $variants = [
            'location' => ['patio de museo con fuente', 'terraza en azotea'],
        ];
        $keywords = [
            'location' => ['museo', 'galería'],
        ];

        // "museo" appears in content AND in "patio de museo con fuente"
        $result = $this->selector->select($variants, 'Evento en el museo de arte', $keywords);

        $this->assertEquals('patio de museo con fuente', $result['location']);
    }

    public function testSelectWithMultipleKeywordMatches(): void
    {
        $variants = [
            'location' => [
                'patio de museo con fuente',
                'terraza con vistas al museo',
                'andén de estación de tren',
            ],
        ];
        $keywords = [
            'location' => ['museo', 'patio'],
        ];

        // Content matches both keywords → should select option with most matches
        // "patio de museo con fuente" contains both "museo" AND "patio" = score 2
        // "terraza con vistas al museo" contains "museo" = score 1
        $result = $this->selector->select($variants, 'Evento en el patio del museo', $keywords);

        $this->assertEquals('patio de museo con fuente', $result['location']);
    }

    public function testSelectWithEmptyCategory(): void
    {
        $variants = [
            'location' => [],
            'presence' => ['figura solitaria'],
        ];

        $result = $this->selector->select($variants, 'test');

        $this->assertArrayNotHasKey('location', $result);
        $this->assertArrayHasKey('presence', $result);
    }

    public function testSelectWithTextSimilarityFallback(): void
    {
        $variants = [
            'location' => ['sendero de jardín en tokyo', 'plaza de madrid'],
        ];

        // No keywords configured, should use text similarity
        // "tokyo" appears in content and in first option
        $result = $this->selector->select($variants, 'Seminario en Tokyo con participantes');

        $this->assertEquals('sendero de jardín en tokyo', $result['location']);
    }

    public function testSelectWithMultipleCategories(): void
    {
        $variants = [
            'location' => ['patio de museo', 'terraza en azotea'],
            'presence' => ['figura solitaria', 'grupo reunido'],
            'mood' => ['celebración', 'reflexión tranquila'],
        ];
        $keywords = [
            'location' => ['museo'],
            'presence' => ['grupo', 'seminario'],
        ];

        // "museo" in content AND in "patio de museo" → location matches
        // "grupo" in content AND in "grupo reunido" → presence matches
        // mood has no keywords, falls back to text similarity or random
        $result = $this->selector->select($variants, 'Seminario en el museo con grupo', $keywords);

        $this->assertEquals('patio de museo', $result['location']);
        $this->assertEquals('grupo reunido', $result['presence']);
        $this->assertArrayHasKey('mood', $result);
    }

    public function testSelectCaseInsensitive(): void
    {
        $variants = [
            'location' => ['TOKYO jardín', 'madrid plaza'],
        ];
        $keywords = [
            'location' => ['Tokyo', 'TOKYO'],
        ];

        // Case insensitive matching
        $result = $this->selector->select($variants, 'evento en tokyo', $keywords);

        $this->assertEquals('TOKYO jardín', $result['location']);
    }

    public function testSelectWithNoMatchesFallsBackToRandom(): void
    {
        $variants = [
            'location' => ['patio de museo', 'terraza en azotea'],
        ];
        $keywords = [
            'location' => ['playa', 'montaña'],
        ];

        // Keywords don't match content AND options → falls back to random
        $result = $this->selector->select($variants, 'Evento en la ciudad', $keywords);

        $this->assertContains($result['location'], $variants['location']);
    }

    public function testKeywordMustBeInBothContentAndOption(): void
    {
        $variants = [
            'mood' => [
                'después de celebración, confeti esparcido',
                'momento de reflexión',
            ],
        ];
        $keywords = [
            'mood' => ['celebración', 'fiesta', 'premio'],
        ];

        // "celebración" is in content AND in first option → should select first
        $result = $this->selector->select(
            $variants,
            'Gran celebración en honor al maestro',
            $keywords
        );

        $this->assertEquals('después de celebración, confeti esparcido', $result['mood']);
    }

    public function testKeywordInContentButNotInOptionDoesNotScore(): void
    {
        $variants = [
            'mood' => [
                'ambiente tranquilo de meditación',
                'momento de reflexión serena',
            ],
        ];
        $keywords = [
            'mood' => ['celebración', 'fiesta'],
        ];

        // "celebración" is in content but NOT in any option
        // Should fall back to random since no keyword appears in both
        $result = $this->selector->select(
            $variants,
            'La celebración fue muy emotiva',
            $keywords
        );

        // Either option is valid since no match was found
        $this->assertContains($result['mood'], $variants['mood']);
    }
}
