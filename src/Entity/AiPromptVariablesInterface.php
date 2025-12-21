<?php

declare(strict_types=1);

namespace Xmon\AiContentBundle\Entity;

/**
 * Interface for entities that provide variables for prompt templates.
 *
 * Implement this interface to provide structured variables that can be used
 * in prompt templates with {placeholder} syntax.
 *
 * Example implementation:
 *
 *     class Article implements AiImageAwareInterface, AiPromptVariablesInterface
 *     {
 *         public function getPromptVariables(): array
 *         {
 *             return [
 *                 'title' => $this->title ?? '',
 *                 'summary' => $this->summary ?? '',
 *                 'content' => $this->getContentForImageGeneration(),
 *             ];
 *         }
 *     }
 *
 * Then in your prompt template (YAML config):
 *
 *     image_subject:
 *         user: "Title: {title}\n\nSummary: {summary}"
 *
 * If this interface is not implemented, the controller falls back to using
 * `getContentForImageGeneration()` as the only variable: {content}
 */
interface AiPromptVariablesInterface
{
    /**
     * Get variables for prompt templates.
     *
     * Returns an associative array where keys are placeholder names
     * (without braces) and values are the strings to substitute.
     *
     * Common variables:
     * - 'title': The entity's title
     * - 'summary': A short description or excerpt
     * - 'content': Full content for generation (from getContentForImageGeneration())
     *
     * @return array<string, string>
     */
    public function getPromptVariables(): array;
}
