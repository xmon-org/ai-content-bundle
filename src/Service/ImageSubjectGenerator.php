<?php

declare(strict_types=1);

namespace Xmon\AiContentBundle\Service;

use Psr\Log\LoggerInterface;
use Xmon\AiContentBundle\Enum\TaskType;

/**
 * Two-step image subject generator.
 *
 * Generates unique, differentiated image subjects from content by:
 * 1. Extracting a visual anchor (distinctive element) from the content
 * 2. Generating a subject that incorporates that anchor prominently
 *
 * Falls back to one-step generation if anchor extraction fails or returns GENERIC.
 *
 * All prompts are configurable via YAML templates.
 */
class ImageSubjectGenerator
{
    private ?array $lastAnchor = null;
    private ?string $lastModel = null;

    /**
     * @param array<string, string> $anchorGuidelines Guidelines by anchor type
     */
    public function __construct(
        private readonly AiTextService $aiTextService,
        private readonly PromptTemplateService $promptTemplateService,
        private readonly array $anchorGuidelines,
        private readonly ?LoggerInterface $logger = null,
    ) {
    }

    /**
     * Generate a subject using two-step process.
     *
     * @param string $title   Content title
     * @param string $summary Content summary/description
     *
     * @return string The generated subject
     */
    public function generate(string $title, string $summary): string
    {
        $this->lastAnchor = null;

        // Step 1: Try to extract visual anchor
        try {
            $anchor = $this->extractAnchor($title, $summary);
            $this->lastAnchor = $anchor;

            if ($anchor !== null && $anchor['isUsable']) {
                $this->logger?->info('[ImageSubjectGenerator] Anchor extracted', [
                    'type' => $anchor['type'],
                    'value' => $anchor['value'],
                ]);

                // Step 2: Generate subject with anchor
                try {
                    $subject = $this->generateWithAnchor($title, $summary, $anchor);
                    $this->logger?->info('[ImageSubjectGenerator] Subject generated with two-step', [
                        'anchorType' => $anchor['type'],
                        'subject' => mb_substr($subject, 0, 80),
                    ]);

                    return $subject;
                } catch (\Exception $e) {
                    $this->logger?->warning('[ImageSubjectGenerator] Step 2 failed, using one-step', [
                        'error' => $e->getMessage(),
                    ]);
                }
            } else {
                $this->logger?->info('[ImageSubjectGenerator] Anchor not usable, using one-step', [
                    'type' => $anchor['type'] ?? 'null',
                ]);
            }
        } catch (\Exception $e) {
            $this->logger?->warning('[ImageSubjectGenerator] Step 1 failed, using one-step', [
                'error' => $e->getMessage(),
            ]);
            $this->lastAnchor = null;
        }

        // Fallback: One-step generation
        return $this->generateOneStep($title, $summary);
    }

    /**
     * Get the last anchor extracted (for debugging/persistence).
     *
     * @return array{type: string, value: string, visual: string, isUsable: bool}|null
     */
    public function getLastAnchor(): ?array
    {
        return $this->lastAnchor;
    }

    /**
     * Get the last model used.
     */
    public function getLastModel(): ?string
    {
        return $this->lastModel;
    }

    /**
     * Step 1: Extract visual anchor from content.
     *
     * @return array{type: string, value: string, visual: string, isUsable: bool}|null
     */
    private function extractAnchor(string $title, string $summary): ?array
    {
        $templateKey = 'anchor_extraction';

        if (!$this->promptTemplateService->hasTemplate($templateKey)) {
            $this->logger?->warning('[ImageSubjectGenerator] Template not found', [
                'template' => $templateKey,
            ]);

            return null;
        }

        $rendered = $this->promptTemplateService->render($templateKey, [
            'titulo' => $title,
            'resumen' => $summary,
            'title' => $title,
            'summary' => $summary,
        ]);

        $result = $this->aiTextService->generateForTask(
            TaskType::IMAGE_PROMPT,
            $rendered['system'],
            $rendered['user'],
            [
                'max_tokens' => 100,
                'temperature' => 0.3,
            ]
        );

        $this->lastModel = $result->getModel();

        return $this->parseAnchorResponse($result->getText());
    }

    /**
     * Parse anchor extraction response.
     *
     * @return array{type: string, value: string, visual: string, isUsable: bool}|null
     */
    private function parseAnchorResponse(string $response): ?array
    {
        $response = trim($response);

        // Parse TYPE, VALUE, VISUAL
        $typeMatch = preg_match('/TYPE:\s*(\w+)/i', $response, $typeMatches);
        $valueMatch = preg_match('/VALUE:\s*(.+?)(?:\n|$)/i', $response, $valueMatches);
        $visualMatch = preg_match('/VISUAL:\s*(.+?)(?:\n|$)/i', $response, $visualMatches);

        if (!$typeMatch || !$valueMatch) {
            $this->logger?->warning('[ImageSubjectGenerator] Could not parse anchor response', [
                'response' => mb_substr($response, 0, 200),
            ]);

            return null;
        }

        $type = strtoupper(trim($typeMatches[1]));
        $value = trim($valueMatches[1]);
        $visual = $visualMatch ? trim($visualMatches[1]) : '';

        // Validate type
        $validTypes = ['PLACE', 'PERSON', 'NUMBER', 'EVENT', 'ORGANIZATION', 'MEMORIAL', 'GENERIC'];
        if (!\in_array($type, $validTypes, true)) {
            $this->logger?->warning('[ImageSubjectGenerator] Invalid anchor type', [
                'type' => $type,
            ]);

            return null;
        }

        // GENERIC is not usable
        $isUsable = $type !== 'GENERIC' && \strlen($value) >= 2;

        return [
            'type' => $type,
            'value' => $value,
            'visual' => $visual,
            'isUsable' => $isUsable,
        ];
    }

    /**
     * Step 2: Generate subject using anchor.
     *
     * @param array{type: string, value: string, visual: string, isUsable: bool} $anchor
     */
    private function generateWithAnchor(string $title, string $summary, array $anchor): string
    {
        $templateKey = 'subject_from_anchor';

        if (!$this->promptTemplateService->hasTemplate($templateKey)) {
            $this->logger?->warning('[ImageSubjectGenerator] Template not found, using one-step', [
                'template' => $templateKey,
            ]);

            return $this->generateOneStep($title, $summary);
        }

        // Get guideline for this anchor type
        $guideline = $this->anchorGuidelines[$anchor['type']]
            ?? $this->anchorGuidelines['default']
            ?? 'Incorporate this element visually in the scene.';

        $rendered = $this->promptTemplateService->render($templateKey, [
            'titulo' => $title,
            'resumen' => $summary,
            'title' => $title,
            'summary' => $summary,
            'anchor_type' => $anchor['type'],
            'anchor_value' => $anchor['value'],
            'anchor_visual' => $anchor['visual'],
            'anchor_guideline' => $guideline,
        ]);

        $result = $this->aiTextService->generateForTask(
            TaskType::IMAGE_PROMPT,
            $rendered['system'],
            $rendered['user'],
            [
                'max_tokens' => 200,
                'temperature' => 0.6,
            ]
        );

        return $this->cleanSubject($result->getText());
    }

    /**
     * Fallback: One-step subject generation.
     */
    private function generateOneStep(string $title, string $summary): string
    {
        $templateKey = 'subject_one_step';

        if (!$this->promptTemplateService->hasTemplate($templateKey)) {
            $this->logger?->warning('[ImageSubjectGenerator] Template not found, using minimal fallback');
            $systemPrompt = 'Create a visual subject for image generation in English, max 40 words.';
            $userMessage = "Title: {$title}";
        } else {
            $rendered = $this->promptTemplateService->render($templateKey, [
                'titulo' => $title,
                'resumen' => $summary,
                'title' => $title,
                'summary' => $summary,
            ]);
            $systemPrompt = $rendered['system'];
            $userMessage = $rendered['user'];
        }

        $result = $this->aiTextService->generateForTask(
            TaskType::IMAGE_PROMPT,
            $systemPrompt,
            $userMessage,
            [
                'max_tokens' => 200,
                'temperature' => 0.7,
            ]
        );

        $this->lastModel = $result->getModel();

        return $this->cleanSubject($result->getText());
    }

    /**
     * Clean LLM response to get subject.
     */
    private function cleanSubject(string $response): string
    {
        $subject = trim($response);

        // Remove quotes
        $subject = preg_replace('/^["\']+|["\']+$/', '', $subject);

        // Remove common LLM prefixes
        $subject = preg_replace('/^(Subject:|Here is|The subject is:?)\s*/i', '', $subject);

        // Normalize whitespace
        $subject = preg_replace('/\s+/', ' ', $subject);

        // Validate minimum length
        if (\strlen($subject) < 20) {
            throw new \RuntimeException('Subject too short: '.$subject);
        }

        return $subject;
    }
}
