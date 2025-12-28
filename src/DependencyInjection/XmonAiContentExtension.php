<?php

declare(strict_types=1);

namespace Xmon\AiContentBundle\DependencyInjection;

use Sonata\AdminBundle\Admin\AdminInterface;
use Sonata\MediaBundle\Model\MediaManagerInterface;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Extension\Extension;
use Symfony\Component\DependencyInjection\Loader\YamlFileLoader;
use Xmon\AiContentBundle\Provider\Image\PollinationsImageProvider;
use Xmon\AiContentBundle\Provider\Text\PollinationsTextProvider;
use Xmon\AiContentBundle\Service\ImageOptionsService;
use Xmon\AiContentBundle\Service\ImageSubjectGenerator;
use Xmon\AiContentBundle\Service\MediaStorageService;
use Xmon\AiContentBundle\Service\ModelRegistryService;
use Xmon\AiContentBundle\Service\PromptTemplateService;
use Xmon\AiContentBundle\Service\TaskConfigService;
use Xmon\AiContentBundle\Twig\AiContentExtension;

class XmonAiContentExtension extends Extension
{
    public function load(array $configs, ContainerBuilder $container): void
    {
        $configuration = new Configuration();
        $config = $this->processConfiguration($configuration, $configs);

        $loader = new YamlFileLoader($container, new FileLocator(__DIR__.'/../../config'));

        // Always load core services (image providers)
        $loader->load('services.yaml');

        // Load text providers
        $loader->load('services_text.yaml');

        // Load SonataMedia integration only if available
        if ($this->isSonataMediaAvailable()) {
            $loader->load('services_media.yaml');
            $this->configureMediaStorage($container, $config['media'] ?? []);
        }

        // Load Sonata Admin integration only if available
        if ($this->isSonataAdminAvailable()) {
            $loader->load('services_admin.yaml');
            $this->configureAdmin($container, $config['admin'] ?? []);
        }

        // Configure providers
        $this->configureImageProviders($container, $config['image'] ?? []);
        $this->configureTextProviders($container, $config['text'] ?? []);

        // Configure task types (models per task)
        $this->configureTaskTypes($container, $config['tasks'] ?? []);

        // Configure image options (styles, compositions, palettes, extras, presets)
        $this->configureImageOptions(
            $container,
            $config['image_options'] ?? [],
            $config['presets'] ?? [],
            $config['disable_preset_defaults'] ?? [],
            $config['default_preset'] ?? null,
            $config['style_suffix'] ?? ''
        );

        // Configure prompt templates
        $this->configurePromptTemplates($container, $config['prompts'] ?? []);

        // Configure history settings
        $this->configureHistory($container, $config['history'] ?? []);

        // Configure image subject generator (two-step anchor system)
        $this->configureImageSubjectGenerator($container, $config['image_subject'] ?? []);
    }

    /**
     * Check if SonataMediaBundle is installed and available.
     */
    private function isSonataMediaAvailable(): bool
    {
        return interface_exists(MediaManagerInterface::class);
    }

    /**
     * Check if SonataAdminBundle is installed and available.
     */
    private function isSonataAdminAvailable(): bool
    {
        return interface_exists(AdminInterface::class);
    }

    private function configureImageProviders(ContainerBuilder $container, array $imageConfig): void
    {
        if ($container->hasDefinition(PollinationsImageProvider::class)) {
            $definition = $container->getDefinition(PollinationsImageProvider::class);
            $definition->setArgument('$apiKey', $imageConfig['api_key'] ?? null);
            $definition->setArgument('$model', $imageConfig['model'] ?? 'flux');
            $definition->setArgument('$fallbackModels', $imageConfig['fallback_models'] ?? []);
            $definition->setArgument('$retriesPerModel', $imageConfig['retries_per_model'] ?? 2);
            $definition->setArgument('$retryDelay', $imageConfig['retry_delay'] ?? 3);
            $definition->setArgument('$timeout', $imageConfig['timeout'] ?? 120);
            $definition->setArgument('$defaultWidth', $imageConfig['width'] ?? 1280);
            $definition->setArgument('$defaultHeight', $imageConfig['height'] ?? 720);
            $definition->setArgument('$quality', $imageConfig['quality'] ?? 'high');
            $definition->setArgument('$negativePrompt', $imageConfig['negative_prompt'] ?? 'worst quality, blurry, text, letters, watermark, human faces, detailed faces');
            $definition->setArgument('$private', $imageConfig['private'] ?? true);
            $definition->setArgument('$nofeed', $imageConfig['nofeed'] ?? true);
        }

        // Store config for external access
        $container->setParameter('xmon_ai_content.image', $imageConfig);
    }

    /**
     * Configure text provider (Pollinations only - simplified architecture Dec 2025).
     *
     * Model selection is now handled by TaskConfigService based on TaskType.
     * The provider handles fallback between models.
     */
    private function configureTextProviders(ContainerBuilder $container, array $textConfig): void
    {
        if ($container->hasDefinition(PollinationsTextProvider::class)) {
            $definition = $container->getDefinition(PollinationsTextProvider::class);
            $definition->setArgument('$apiKey', $textConfig['api_key'] ?? null);
            $definition->setArgument('$model', $textConfig['model'] ?? 'mistral');
            $definition->setArgument('$fallbackModels', $textConfig['fallback_models'] ?? []);
            $definition->setArgument('$retriesPerModel', $textConfig['retries_per_model'] ?? 2);
            $definition->setArgument('$retryDelay', $textConfig['retry_delay'] ?? 3);
            $definition->setArgument('$timeout', $textConfig['timeout'] ?? 60);
            $definition->setArgument('$endpointMode', $textConfig['endpoint_mode'] ?? 'openai');
        }

        // Store config for external access
        $container->setParameter('xmon_ai_content.text', $textConfig);
    }

    /**
     * Configure task types with their default/allowed models.
     *
     * This bridges the ModelRegistryService (catalog of available models with costs)
     * with user configuration (which models are enabled for each task type).
     *
     * The ModelRegistryService contains immutable provider data (model names, costs).
     * The Configuration.php schema allows users to customize which models are allowed
     * for each task type in their specific project.
     *
     * @param array<string, array{default_model?: string, allowed_models?: list<string>}> $tasksConfig
     */
    private function configureTaskTypes(ContainerBuilder $container, array $tasksConfig): void
    {
        // ModelRegistryService has no config arguments - it's a pure catalog
        // TaskConfigService receives the tasks configuration
        if ($container->hasDefinition(TaskConfigService::class)) {
            $definition = $container->getDefinition(TaskConfigService::class);
            $definition->setArgument('$tasksConfig', $tasksConfig);
        }

        // Store config as parameter for external access
        $container->setParameter('xmon_ai_content.tasks', $tasksConfig);
    }

    private function configureAdmin(ContainerBuilder $container, array $adminConfig): void
    {
        $baseTemplate = $adminConfig['base_template'] ?? '@SonataAdmin/standard_layout.html.twig';
        $showBundleCredit = $adminConfig['show_bundle_credit'] ?? true;

        $container->setParameter('xmon_ai_content.admin.base_template', $baseTemplate);
        $container->setParameter('xmon_ai_content.admin.show_bundle_credit', $showBundleCredit);

        // Configure Twig extension arguments directly (YAML loads before parameters are set)
        if ($container->hasDefinition(AiContentExtension::class)) {
            $container->getDefinition(AiContentExtension::class)
                ->setArguments([
                    $baseTemplate,
                    $showBundleCredit,
                ]);
        }
    }

    private function configureMediaStorage(ContainerBuilder $container, array $mediaConfig): void
    {
        $defaultContext = $mediaConfig['default_context'] ?? 'default';
        $providerName = $mediaConfig['provider'] ?? 'sonata.media.provider.image';

        if ($container->hasDefinition(MediaStorageService::class)) {
            $definition = $container->getDefinition(MediaStorageService::class);
            $definition->setArgument('$defaultContext', $defaultContext);
            $definition->setArgument('$providerName', $providerName);
        }

        // Store parameters
        $container->setParameter('xmon_ai_content.media.default_context', $defaultContext);
        $container->setParameter('xmon_ai_content.media.provider', $providerName);
    }

    private function configureImageOptions(ContainerBuilder $container, array $imageOptions, array $userPresets, array $disablePresetDefaults, ?string $defaultPreset = null, string $styleSuffix = ''): void
    {
        // Get disable lists
        $disableDefaults = $imageOptions['disable_defaults'] ?? [];
        $disableStyles = $disableDefaults['styles'] ?? [];
        $disableCompositions = $disableDefaults['compositions'] ?? [];
        $disablePalettes = $disableDefaults['palettes'] ?? [];
        $disableExtras = $disableDefaults['extras'] ?? [];

        // Bundle defaults - user config is MERGED with these (user wins on conflict)
        // Keys use kebab-case for consistency with YAML conventions
        $defaultStyles = [
            'sumi-e' => [
                'label' => 'Sumi-e (tinta japonesa)',
                'prompt' => 'sumi-e Japanese ink wash painting, elegant brushstrokes, traditional',
            ],
            'watercolor' => [
                'label' => 'Acuarela',
                'prompt' => 'delicate watercolor painting, soft edges, flowing pigments',
            ],
            'oil-painting' => [
                'label' => 'Óleo clásico',
                'prompt' => 'classical oil painting style, rich textures, masterful brushwork',
            ],
            'digital-art' => [
                'label' => 'Arte digital',
                'prompt' => 'modern digital art, clean lines, vibrant rendering',
            ],
            'photography' => [
                'label' => 'Fotografía artística',
                'prompt' => 'professional photography, dramatic lighting, shallow depth of field',
            ],
        ];

        $defaultCompositions = [
            'centered' => [
                'label' => 'Centrada',
                'prompt' => 'centered subject, symmetrical balance, clear focal point',
            ],
            'rule-of-thirds' => [
                'label' => 'Regla de tercios',
                'prompt' => 'rule of thirds composition, dynamic placement, visual flow',
            ],
            'negative-space' => [
                'label' => 'Espacio negativo',
                'prompt' => 'generous negative space, minimalist, breathing room',
            ],
            'panoramic' => [
                'label' => 'Panorámica',
                'prompt' => 'wide panoramic view, expansive scene, horizontal emphasis',
            ],
            'close-up' => [
                'label' => 'Primer plano',
                'prompt' => 'intimate close-up, detail focus, cropped composition',
            ],
        ];

        $defaultPalettes = [
            'monochrome' => [
                'label' => 'Monocromo',
                'prompt' => 'monochromatic color scheme, single hue variations, elegant simplicity',
            ],
            'earth-tones' => [
                'label' => 'Tonos tierra',
                'prompt' => 'warm earth tones, browns, ochres, natural organic colors',
            ],
            'japanese-traditional' => [
                'label' => 'Tradicional japonés',
                'prompt' => 'traditional Japanese palette, indigo, vermillion, gold leaf accents',
            ],
            'muted' => [
                'label' => 'Colores apagados',
                'prompt' => 'muted desaturated colors, soft tones, subtle elegance',
            ],
            'high-contrast' => [
                'label' => 'Alto contraste',
                'prompt' => 'high contrast, bold blacks and whites, dramatic tonal range',
            ],
        ];

        $defaultExtras = [
            'no-text' => [
                'label' => 'Sin texto',
                'prompt' => 'no text, no letters, no words, no typography',
            ],
            'silhouettes' => [
                'label' => 'Siluetas',
                'prompt' => 'silhouette figures, no detailed faces, anonymous forms',
            ],
            'atmospheric' => [
                'label' => 'Atmosférico',
                'prompt' => 'atmospheric perspective, misty, ethereal quality',
            ],
            'dramatic-light' => [
                'label' => 'Luz dramática',
                'prompt' => 'dramatic lighting, chiaroscuro, strong shadows',
            ],
        ];

        $defaultPresets = [
            'sumi-e-clasico' => [
                'name' => 'Sumi-e Clásico',
                'style' => 'sumi-e',
                'composition' => 'negative-space',
                'palette' => 'monochrome',
                'extras' => ['no-text', 'silhouettes', 'atmospheric'],
            ],
            'zen-contemplativo' => [
                'name' => 'Zen Contemplativo',
                'style' => 'sumi-e',
                'composition' => 'centered',
                'palette' => 'muted',
                'extras' => ['no-text', 'atmospheric'],
            ],
            'fotografia-aikido' => [
                'name' => 'Fotografía Aikido',
                'style' => 'photography',
                'composition' => 'rule-of-thirds',
                'palette' => 'muted',
                'extras' => ['dramatic-light'],
            ],
        ];

        // Filter out disabled defaults
        $defaultStyles = array_diff_key($defaultStyles, array_flip($disableStyles));
        $defaultCompositions = array_diff_key($defaultCompositions, array_flip($disableCompositions));
        $defaultPalettes = array_diff_key($defaultPalettes, array_flip($disablePalettes));
        $defaultExtras = array_diff_key($defaultExtras, array_flip($disableExtras));
        $defaultPresets = array_diff_key($defaultPresets, array_flip($disablePresetDefaults));

        // Merge: filtered defaults first, then user config (user wins on same key)
        $styles = array_merge($defaultStyles, $imageOptions['styles'] ?? []);
        $compositions = array_merge($defaultCompositions, $imageOptions['compositions'] ?? []);
        $palettes = array_merge($defaultPalettes, $imageOptions['palettes'] ?? []);
        $extras = array_merge($defaultExtras, $imageOptions['extras'] ?? []);
        $presets = array_merge($defaultPresets, $userPresets);

        // Filter out presets that reference disabled options
        $presets = $this->filterBrokenPresets($presets, $styles, $compositions, $palettes, $extras);

        // Configure ImageOptionsService
        if ($container->hasDefinition(ImageOptionsService::class)) {
            $definition = $container->getDefinition(ImageOptionsService::class);
            $definition->setArgument('$styles', $styles);
            $definition->setArgument('$compositions', $compositions);
            $definition->setArgument('$palettes', $palettes);
            $definition->setArgument('$extras', $extras);
            $definition->setArgument('$presets', $presets);
            $definition->setArgument('$styleSuffix', $styleSuffix);
        }

        // Store parameters for external access
        $container->setParameter('xmon_ai_content.image_options.styles', $styles);
        $container->setParameter('xmon_ai_content.image_options.compositions', $compositions);
        $container->setParameter('xmon_ai_content.image_options.palettes', $palettes);
        $container->setParameter('xmon_ai_content.image_options.extras', $extras);
        $container->setParameter('xmon_ai_content.presets', $presets);

        // Default preset: validate it exists, otherwise use first available
        $resolvedDefaultPreset = null;
        if ($defaultPreset !== null && isset($presets[$defaultPreset])) {
            $resolvedDefaultPreset = $defaultPreset;
        } elseif (!empty($presets)) {
            $resolvedDefaultPreset = array_key_first($presets);
        }
        $container->setParameter('xmon_ai_content.default_preset', $resolvedDefaultPreset);
        $container->setParameter('xmon_ai_content.style_suffix', $styleSuffix);
    }

    private function configurePromptTemplates(ContainerBuilder $container, array $promptsConfig): void
    {
        $disableDefaults = $promptsConfig['disable_defaults'] ?? [];
        $userTemplates = $promptsConfig['templates'] ?? [];

        // Bundle default prompts
        $defaultTemplates = [
            // Two-step image subject generation templates
            'anchor_extraction' => [
                'name' => 'Anchor Extraction',
                'description' => 'Extracts unique visual anchor from content for image differentiation',
                'system' => <<<'PROMPT'
You are an expert at identifying unique visual elements in news articles.

TASK: Analyze this content and extract THE SINGLE MOST DISTINCTIVE visual element.

CLASSIFICATION (choose ONE):
- PLACE: Specific location (city, region, country, venue name)
- PERSON: Named individual (instructor, master, notable person)
- NUMBER: Anniversary, edition, year, significant number
- EVENT: Specific event type (seminar, examination, gala, competition)
- ORGANIZATION: Federation, association, school, institution
- MEMORIAL: Death, tribute, homage
- GENERIC: ONLY if absolutely nothing specific found

OUTPUT FORMAT (exactly 3 lines, no extra text):
TYPE: [category]
VALUE: [extracted value in original language]
VISUAL: [brief visual hint in English, max 10 words]

Example for "50th Anniversary of Aikido in Galicia":
TYPE: PLACE
VALUE: Galicia
VISUAL: rocky Atlantic coastline with Celtic stone monuments
PROMPT,
                'user' => "Title: {title}\n\nSummary: {summary}",
            ],
            'subject_from_anchor' => [
                'name' => 'Subject from Anchor',
                'description' => 'Generates image subject incorporating extracted anchor',
                'system' => <<<'PROMPT'
You are an expert in prompts for artistic image generation.

MANDATORY ANCHOR (must be visually present in the scene):
- Type: {anchor_type}
- Element: {anchor_value}
- Visual interpretation: {anchor_visual}

ANCHOR GUIDELINE: {anchor_guideline}

Create ONE SUBJECT (maximum 40 words) that:
1. MUST incorporate the anchor element prominently and specifically
2. Combines naturally with martial arts/traditional imagery (silhouettes, traditional clothing)
3. Creates a UNIQUE scene that could NOT be used for any other content

STRICT RULES:
- NO text, letters, or words in the image
- NO detailed faces (use silhouettes, anonymous figures)
- NO aggressive poses or combat action
- NO style/lighting/color language (added separately)
- Use STATIC, contemplative, or ceremonial poses only

RESPOND ONLY with the subject in English, no explanations, quotes, or preamble.
PROMPT,
                'user' => "Title: {title}\n\nSummary: {summary}",
            ],
            'subject_one_step' => [
                'name' => 'Subject One-Step (Fallback)',
                'description' => 'Generates image subject without anchor (fallback)',
                'system' => <<<'PROMPT'
You are an expert in prompts for artistic image generation.

Create ONE SUBJECT (maximum 40 words) for an image based on the content below.

REQUIREMENTS:
- Include appropriate traditional/professional elements
- Use STATIC or meditative poses
- Traditional elements that match the content theme
- Serene, respectful, professional atmosphere

DO NOT include:
- Text, letters, or words
- Detailed faces (use silhouettes)
- Aggressive action poses
- Style, lighting, or color descriptions

RESPOND ONLY with the subject in English, no explanations.
PROMPT,
                'user' => "Title: {title}\n\nSummary: {summary}",
            ],
            // Original one-step template (legacy)
            'image_subject' => [
                'name' => 'Image Subject Generator',
                'description' => 'Generates a visual subject description for image generation from news/content. Uses two-step classification for accurate categorization.',
                'system' => <<<'PROMPT'
You are an expert in prompts for artistic image generation.

STEP 1 - ANALYZE the content and identify:
- Category: TRIBUTE/DEATH | CELEBRATION/AWARD | SEMINAR/COURSE | FEDERATION/INSTITUTION | TECHNIQUE/PRACTICE | GENERAL
- Emotional tone: solemn, celebratory, educational, institutional, reflective
- Key visual concept: what single scene best represents this content?

STEP 2 - CREATE ONE SUBJECT (maximum 40 words) following these rules by category:

TRIBUTE/DEATH: memorial atmosphere, single practitioner silhouette in seiza, falling petals, empty dojo, respectful solitude
CELEBRATION/AWARD: subtle golden accents, ceremonial bow, warm lighting, achievement feeling
SEMINAR/COURSE: multiple silhouettes practicing together, instructor demonstrating, attentive students
FEDERATION/INSTITUTION: enso circle, unity of practitioners, formal atmosphere, group harmony
TECHNIQUE/PRACTICE: focused practitioner, precise stance, traditional elements
GENERAL: serene dojo atmosphere, meditative mood, traditional elements

ALWAYS INCLUDE: martial arts elements (tatami, traditional clothing), static/meditative poses, silhouettes (no detailed faces)
NEVER INCLUDE: text/letters, aggressive combat, dynamic fighting poses, bright saturated colors

OUTPUT: Only the subject in English, no explanations, no quotes, no category label.
PROMPT,
                'user' => "Title: {title}\n\nSummary: {summary}",
            ],
            'summarizer' => [
                'name' => 'Content Summarizer',
                'description' => 'Summarizes content while preserving key information and tone.',
                'system' => <<<'PROMPT'
You are an expert content summarizer. Your task is to create concise, accurate summaries that:

1. Preserve the key information and main points
2. Maintain the original tone and intent
3. Be clear and readable
4. Avoid adding opinions or information not in the original

Guidelines:
- Focus on WHO, WHAT, WHEN, WHERE, WHY
- Use active voice when possible
- Keep the summary proportional to the original length
- Preserve important names, dates, and specific details
PROMPT,
                'user' => '{content}',
            ],
            'title_generator' => [
                'name' => 'Title Generator',
                'description' => 'Generates engaging titles for content.',
                'system' => <<<'PROMPT'
You are an expert headline writer. Create compelling, accurate titles that:

1. Capture the essence of the content
2. Are engaging but not clickbait
3. Are appropriate length (50-70 characters ideal)
4. Use active voice when possible

Guidelines:
- Be specific, not vague
- Avoid sensationalism
- Include key information
- Match the tone of the content
PROMPT,
                'user' => "Content:\n{content}\n\nGenerate a title.",
            ],
            'content_generator' => [
                'name' => 'All-in-One Content Generator',
                'description' => 'Generates all content fields in a single API call (title, summary, SEO, image subject). Returns JSON.',
                'system' => <<<'PROMPT'
You are an expert content generator. Given raw content, generate ALL fields in a single JSON response.

OUTPUT FORMAT (valid JSON only, no markdown):
{
    "title": "Engaging title (50-70 characters)",
    "summary": "Brief summary (2-3 sentences, max 200 characters)",
    "metaTitle": "SEO optimized title (max 60 characters)",
    "metaDescription": "SEO meta description (150-160 characters)",
    "imageSubject": "Visual subject for image generation (max 40 words, English)"
}

GUIDELINES:
- title: Engaging, accurate, not clickbait
- summary: Key information, WHO/WHAT/WHEN/WHERE
- metaTitle: Include main keyword, compelling for search results
- metaDescription: Call to action, include keyword naturally
- imageSubject:
  * First classify content: TRIBUTE | CELEBRATION | SEMINAR | INSTITUTION | TECHNIQUE | GENERAL
  * TRIBUTE: memorial atmosphere, silhouette in seiza, falling petals
  * CELEBRATION: golden accents, ceremonial bow, warm lighting
  * SEMINAR: multiple silhouettes practicing, instructor demonstrating
  * INSTITUTION: enso circle, unity, formal atmosphere
  * TECHNIQUE: focused practitioner, precise stance
  * GENERAL: serene dojo, meditative mood
  * ALWAYS: silhouettes (no faces), static poses, traditional elements
  * NEVER: text/letters, aggressive combat, bright colors

OUTPUT: Only valid JSON, no explanations, no markdown code blocks.
PROMPT,
                'user' => '{content}',
            ],
        ];

        // Filter out disabled defaults
        $defaultTemplates = array_diff_key($defaultTemplates, array_flip($disableDefaults));

        // Merge: defaults first, user templates override
        $templates = array_merge($defaultTemplates, $userTemplates);

        // Configure PromptTemplateService
        if ($container->hasDefinition(PromptTemplateService::class)) {
            $definition = $container->getDefinition(PromptTemplateService::class);
            $definition->setArgument('$templates', $templates);
        }

        // Store parameters for external access
        $container->setParameter('xmon_ai_content.prompts.templates', $templates);
    }

    private function configureHistory(ContainerBuilder $container, array $historyConfig): void
    {
        $maxImages = $historyConfig['max_images'] ?? 5;
        $container->setParameter('xmon_ai_content.history.max_images', $maxImages);
    }

    public function getAlias(): string
    {
        return 'xmon_ai_content';
    }

    /**
     * Filter out presets that reference non-existent options (due to disabled defaults).
     */
    private function filterBrokenPresets(array $presets, array $styles, array $compositions, array $palettes, array $extras): array
    {
        return array_filter($presets, function (array $preset) use ($styles, $compositions, $palettes, $extras): bool {
            // Check if style exists (if specified)
            if (!empty($preset['style']) && !isset($styles[$preset['style']])) {
                return false;
            }

            // Check if composition exists (if specified)
            if (!empty($preset['composition']) && !isset($compositions[$preset['composition']])) {
                return false;
            }

            // Check if palette exists (if specified)
            if (!empty($preset['palette']) && !isset($palettes[$preset['palette']])) {
                return false;
            }

            // Check if all extras exist (if specified)
            foreach ($preset['extras'] ?? [] as $extra) {
                if (!isset($extras[$extra])) {
                    return false;
                }
            }

            return true;
        });
    }

    /**
     * Configure ImageSubjectGenerator with anchor type guidelines.
     *
     * Default guidelines are provided for common anchor types.
     * Projects can override or add new types via configuration.
     */
    private function configureImageSubjectGenerator(ContainerBuilder $container, array $imageSubjectConfig): void
    {
        // Default anchor type guidelines (generic, domain-agnostic)
        $defaultGuidelines = [
            'PLACE' => 'Include distinctive regional landscape, architecture, or natural elements from this location.',
            'PERSON' => 'Feature a distinguished silhouette (NEVER detailed face) representing this individual.',
            'NUMBER' => 'Feature the number prominently - as golden numerals, symbolic element, or visual pattern.',
            'EVENT' => 'Show specific event atmosphere - gathering energy, formality, celebration mood.',
            'ORGANIZATION' => 'Include institutional symbols, unity elements, or formal group atmosphere.',
            'MEMORIAL' => 'Solemn respectful atmosphere, falling petals or leaves, solitary distinguished silhouette.',
            'default' => 'Incorporate this element visually in the scene.',
        ];

        // Merge user guidelines (user overrides defaults)
        $userGuidelines = $imageSubjectConfig['anchor_types'] ?? [];
        $guidelines = array_merge($defaultGuidelines, $userGuidelines);

        // Configure ImageSubjectGenerator
        if ($container->hasDefinition(ImageSubjectGenerator::class)) {
            $definition = $container->getDefinition(ImageSubjectGenerator::class);
            $definition->setArgument('$anchorGuidelines', $guidelines);
        }

        // Store parameters for external access
        $container->setParameter('xmon_ai_content.image_subject.anchor_types', $guidelines);
    }
}
