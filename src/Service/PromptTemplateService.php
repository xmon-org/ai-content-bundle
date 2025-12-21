<?php

declare(strict_types=1);

namespace Xmon\AiContentBundle\Service;

use Psr\Log\LoggerInterface;
use Xmon\AiContentBundle\Exception\AiProviderException;

/**
 * Service for managing configurable prompt templates.
 *
 * Provides access to system and user prompts configured via YAML.
 * Each prompt template has:
 * - name: Human-readable name
 * - description: Optional explanation of what the prompt does
 * - system: The system prompt (instructions for the AI)
 * - user: The user message template (supports {variable} placeholders)
 * - variants: Optional category => options array for dynamic injection
 * - variant_keywords: Optional keywords for intelligent variant selection
 *
 * Variants are selected based on content analysis. For best results,
 * keep variants and keywords in the same language as the content.
 * Request output in a different language via the system prompt if needed.
 *
 * Used by services that need to generate text with configurable prompts.
 */
class PromptTemplateService
{
    /**
     * @param array<string, array{name: string, description: ?string, system: string, user: string, variants?: array<string, string[]>, variant_keywords?: array<string, string[]>}> $templates
     */
    public function __construct(
        private readonly array $templates,
        private readonly VariantSelector $variantSelector,
        private readonly ?LoggerInterface $logger = null,
    ) {
    }

    // ==========================================
    // GETTERS FOR UI (list available prompts)
    // ==========================================

    /**
     * Get all available prompt templates as key => name pairs.
     *
     * @return array<string, string>
     */
    public function getTemplates(): array
    {
        $result = [];
        foreach ($this->templates as $key => $template) {
            $result[$key] = $template['name'];
        }

        return $result;
    }

    /**
     * Get all template keys.
     *
     * @return string[]
     */
    public function getTemplateKeys(): array
    {
        return array_keys($this->templates);
    }

    // ==========================================
    // TEMPLATE ACCESS
    // ==========================================

    /**
     * Check if a template exists.
     */
    public function hasTemplate(string $key): bool
    {
        return isset($this->templates[$key]);
    }

    /**
     * Get the full template data.
     *
     * @return array{name: string, description: ?string, system: string, user: string}|null
     */
    public function getTemplate(string $key): ?array
    {
        return $this->templates[$key] ?? null;
    }

    /**
     * Get the system prompt for a template.
     */
    public function getSystemPrompt(string $key): ?string
    {
        return $this->templates[$key]['system'] ?? null;
    }

    /**
     * Get the user message template for a template.
     */
    public function getUserTemplate(string $key): ?string
    {
        return $this->templates[$key]['user'] ?? null;
    }

    /**
     * Get the description for a template.
     */
    public function getDescription(string $key): ?string
    {
        return $this->templates[$key]['description'] ?? null;
    }

    // ==========================================
    // TEMPLATE RENDERING
    // ==========================================

    /**
     * Render a user message template with variables.
     *
     * Variables in the template use {variable_name} syntax.
     * Example: "Title: {title}\nContent: {content}"
     *
     * @param string                $key       The template key
     * @param array<string, string> $variables Key-value pairs to replace in template
     *
     * @throws AiProviderException If template not found
     */
    public function renderUserMessage(string $key, array $variables): string
    {
        $template = $this->getUserTemplate($key);

        if ($template === null) {
            throw new AiProviderException(\sprintf('Prompt template not found: %s', $key));
        }

        return $this->replaceVariables($template, $variables);
    }

    /**
     * Get both system and rendered user message for a template.
     *
     * If the template has variants configured, they will be selected based on
     * content analysis and injected into the system prompt using {variant_X} placeholders.
     *
     * @param string                $key       The template key
     * @param array<string, string> $variables Variables for user message template
     *
     * @throws AiProviderException If template not found or variant placeholder without variants
     *
     * @return array{system: string, user: string, selected_variants?: array<string, string>}
     */
    public function render(string $key, array $variables = []): array
    {
        if (!$this->hasTemplate($key)) {
            throw new AiProviderException(\sprintf('Prompt template not found: %s', $key));
        }

        $template = $this->templates[$key];
        $system = $template['system'];

        // Process variants if defined
        $selectedVariants = [];
        if (!empty($template['variants'])) {
            // Validate that all placeholders have variants defined
            preg_match_all('/{variant_(\w+)}/', $system, $matches);
            $requiredCategories = $matches[1];

            foreach ($requiredCategories as $category) {
                if (!isset($template['variants'][$category])) {
                    throw new AiProviderException(\sprintf('Template "%s" uses {variant_%s} but no variants defined for category "%s"', $key, $category, $category));
                }
            }

            // Build content for analysis
            $contentForAnalysis = trim(implode(' ', [
                $variables['title'] ?? '',
                $variables['summary'] ?? '',
                $variables['content'] ?? '',
            ]));

            if (empty($contentForAnalysis)) {
                $this->logger?->warning('[PromptTemplateService] No content available for variant selection, using random', [
                    'template' => $key,
                ]);
            }

            // Select variants with optional keywords
            $selectedVariants = $this->variantSelector->select(
                $template['variants'],
                $contentForAnalysis,
                $template['variant_keywords'] ?? null,
            );

            // Replace placeholders in system prompt
            foreach ($selectedVariants as $category => $value) {
                $system = str_replace('{variant_'.$category.'}', $value, $system);
            }

            $this->logger?->info('[PromptTemplateService] Variants injected into system prompt', [
                'template' => $key,
                'variants' => $selectedVariants,
            ]);
        }

        // Replace user variables in both system and user prompts
        $result = [
            'system' => $this->replaceVariables($system, $variables),
            'user' => $this->renderUserMessage($key, $variables),
        ];

        if (!empty($selectedVariants)) {
            $result['selected_variants'] = $selectedVariants;
        }

        return $result;
    }

    // ==========================================
    // RAW DATA ACCESS
    // ==========================================

    /**
     * Get all raw template data.
     *
     * @return array<string, array{name: string, description: ?string, system: string, user: string}>
     */
    public function getAllTemplates(): array
    {
        return $this->templates;
    }

    // ==========================================
    // PRIVATE HELPERS
    // ==========================================

    /**
     * Replace {variable} placeholders in a template string.
     *
     * @param array<string, string> $variables
     */
    private function replaceVariables(string $template, array $variables): string
    {
        foreach ($variables as $name => $value) {
            $template = str_replace('{'.$name.'}', $value, $template);
        }

        return $template;
    }
}
