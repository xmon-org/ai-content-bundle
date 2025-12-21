<?php

declare(strict_types=1);

namespace Xmon\AiContentBundle\Service;

use Psr\Log\LoggerInterface;

/**
 * Selects variants from configured options based on content analysis.
 *
 * Uses keyword matching (if configured) or text similarity to select
 * the most appropriate variant for each category based on the input content.
 * Falls back to random selection when no matches are found.
 *
 * For best results, content and options should be in the same language.
 * The selected variant can then be used in a prompt that requests output
 * in a different language (e.g., Spanish options → English output for images).
 */
class VariantSelector
{
    private const STOPWORDS = [
        'the', 'and', 'with', 'from', 'for', 'una', 'con', 'del', 'los',
        'in', 'at', 'to', 'of', 'by', 'on', 'as', 'is', 'was', 'are',
        'un', 'la', 'el', 'de', 'en', 'por', 'para', 'que', 'se',
    ];

    public function __construct(
        private readonly ?LoggerInterface $logger = null,
    ) {
    }

    /**
     * Selects variants based on content keywords.
     *
     * When content and options are in the same language, matching is straightforward.
     * Keywords found in content are matched against options containing those same keywords.
     *
     * @param array<string, string[]>      $variants Category => options array
     * @param string                       $content  Text to analyze (title + summary)
     * @param array<string, string[]>|null $keywords Category => keywords for matching (optional)
     *
     * @return array<string, string> Category => selected option
     */
    public function select(
        array $variants,
        string $content = '',
        ?array $keywords = null,
    ): array {
        $selected = [];
        $contentLower = mb_strtolower($content);

        foreach ($variants as $category => $options) {
            if (empty($options)) {
                continue;
            }

            // If keywords are configured for this category, use them
            $categoryKeywords = $keywords[$category] ?? null;

            $bestMatch = null;

            // Try keyword matching first
            if ($categoryKeywords !== null) {
                $bestMatch = $this->findBestMatchWithKeywords($options, $contentLower, $categoryKeywords);
            }

            // Fallback to text similarity if keyword matching failed or no keywords
            if ($bestMatch === null) {
                $bestMatch = $this->findBestMatchByTextSimilarity($options, $contentLower);
            }

            // Final fallback: random selection
            $selected[$category] = $bestMatch ?? $options[array_rand($options)];
        }

        $this->logger?->debug('[VariantSelector] Selected variants', [
            'content_preview' => substr($content, 0, 100),
            'variants' => $selected,
        ]);

        return $selected;
    }

    /**
     * Finds the best matching option using explicit keywords.
     *
     * A keyword must appear in BOTH the content AND the option to score.
     * This requires content and options to be in the same language.
     *
     * @param string[] $options  Available options
     * @param string   $content  Lowercased content to search in
     * @param string[] $keywords Keywords to match
     */
    private function findBestMatchWithKeywords(
        array $options,
        string $content,
        array $keywords,
    ): ?string {
        $scores = [];

        foreach ($options as $index => $option) {
            $optionLower = mb_strtolower($option);
            $score = 0;

            foreach ($keywords as $keyword) {
                $keywordLower = mb_strtolower($keyword);

                // Keyword must be in content AND in option to score
                if (str_contains($content, $keywordLower) && str_contains($optionLower, $keywordLower)) {
                    ++$score;
                }
            }

            if ($score > 0) {
                $scores[$index] = $score;
            }
        }

        if (empty($scores)) {
            return null;
        }

        arsort($scores);

        return $options[array_key_first($scores)];
    }

    /**
     * Fallback: extracts significant words from each option and searches in content.
     *
     * This method works best when content and options are in the same language.
     *
     * @param string[] $options Available options
     * @param string   $content Lowercased content to search in
     */
    private function findBestMatchByTextSimilarity(
        array $options,
        string $content,
    ): ?string {
        $scores = [];

        foreach ($options as $index => $option) {
            $optionWords = $this->extractSignificantWords($option);

            $score = 0;
            foreach ($optionWords as $word) {
                if (str_contains($content, $word)) {
                    ++$score;
                }
            }

            if ($score > 0) {
                $scores[$index] = $score;
            }
        }

        if (empty($scores)) {
            return null;
        }

        arsort($scores);

        return $options[array_key_first($scores)];
    }

    /**
     * Extracts significant words from text (> 3 chars, no stopwords).
     *
     * @return string[]
     */
    private function extractSignificantWords(string $text): array
    {
        $words = preg_split('/[\s,]+/', mb_strtolower($text));

        if ($words === false) {
            return [];
        }

        return array_values(array_filter($words, function ($word) {
            return mb_strlen($word) > 3 && !\in_array($word, self::STOPWORDS, true);
        }));
    }
}
