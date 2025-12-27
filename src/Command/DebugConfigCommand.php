<?php

declare(strict_types=1);

namespace Xmon\AiContentBundle\Command;

use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Xmon\AiContentBundle\Enum\TaskType;
use Xmon\AiContentBundle\Service\AiImageService;
use Xmon\AiContentBundle\Service\AiTextService;
use Xmon\AiContentBundle\Service\ImageOptionsService;
use Xmon\AiContentBundle\Service\PromptTemplateService;
use Xmon\AiContentBundle\Service\TaskConfigService;

#[AsCommand(
    name: 'xmon:ai:debug',
    description: 'Display the current configuration of xmon-org/ai-content-bundle',
)]
class DebugConfigCommand extends Command
{
    public function __construct(
        private readonly AiTextService $textService,
        private readonly AiImageService $imageService,
        private readonly ImageOptionsService $imageOptions,
        private readonly PromptTemplateService $promptTemplates,
        private readonly TaskConfigService $taskConfig,
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $io->title('xmon-org/ai-content-bundle Configuration');

        // Provider Status (Pollinations-only architecture since Dec 2025)
        $io->section('Provider Status');
        $textProviders = $this->textService->getAvailableProviders();
        $imageProviders = $this->imageService->getAvailableProviders();

        $textOk = \in_array('pollinations', $textProviders, true);
        $imageOk = \in_array('pollinations', $imageProviders, true);

        $io->table(
            ['Service', 'Status', 'Provider'],
            [
                ['Text Generation', $textOk ? '<info>✓</info>' : '<error>✗</error>', 'Pollinations API'],
                ['Image Generation', $imageOk ? '<info>✓</info>' : '<error>✗</error>', 'Pollinations API'],
            ]
        );

        if (!$textOk || !$imageOk) {
            $io->warning('Some providers are not available. Check XMON_AI_POLLINATIONS_API_KEY in .env');
        }

        // Task Models Configuration
        $io->section('Task Models');
        $taskRows = [];
        foreach (TaskType::cases() as $taskType) {
            $defaultModel = $this->taskConfig->getDefaultModel($taskType);
            $allowedModels = $this->taskConfig->getAllowedModels($taskType);
            $costEstimate = $this->taskConfig->getDefaultCostEstimate($taskType);

            $taskRows[] = [
                $taskType->value,
                $defaultModel,
                $costEstimate['formattedCost'],
                implode(', ', $allowedModels),
            ];
        }
        $io->table(
            ['Task', 'Default Model', 'Cost', 'Allowed Models'],
            $taskRows
        );

        // Styles
        $io->section('Styles');
        $styles = $this->imageOptions->getStyles();
        if (empty($styles)) {
            $io->text('No styles configured');
        } else {
            $io->table(
                ['Key', 'Label'],
                array_map(fn ($k, $v) => [$k, $v], array_keys($styles), array_values($styles))
            );
        }

        // Compositions
        $io->section('Compositions');
        $compositions = $this->imageOptions->getCompositions();
        if (empty($compositions)) {
            $io->text('No compositions configured');
        } else {
            $io->table(
                ['Key', 'Label'],
                array_map(fn ($k, $v) => [$k, $v], array_keys($compositions), array_values($compositions))
            );
        }

        // Palettes
        $io->section('Palettes');
        $palettes = $this->imageOptions->getPalettes();
        if (empty($palettes)) {
            $io->text('No palettes configured');
        } else {
            $io->table(
                ['Key', 'Label'],
                array_map(fn ($k, $v) => [$k, $v], array_keys($palettes), array_values($palettes))
            );
        }

        // Extras
        $io->section('Extras');
        $extras = $this->imageOptions->getExtras();
        if (empty($extras)) {
            $io->text('No extras configured');
        } else {
            $io->table(
                ['Key', 'Label'],
                array_map(fn ($k, $v) => [$k, $v], array_keys($extras), array_values($extras))
            );
        }

        // Presets
        $io->section('Presets');
        $presets = $this->imageOptions->getPresets();
        if (empty($presets)) {
            $io->text('No presets configured');
        } else {
            $rows = [];
            foreach ($presets as $key => $name) {
                $preset = $this->imageOptions->getPreset($key);
                $rows[] = [
                    $key,
                    $name,
                    $preset['style'] ?? '-',
                    $preset['composition'] ?? '-',
                    $preset['palette'] ?? '-',
                    implode(', ', $preset['extras'] ?? []) ?: '-',
                ];
            }
            $io->table(
                ['Key', 'Name', 'Style', 'Composition', 'Palette', 'Extras'],
                $rows
            );
        }

        // Prompt Templates
        $io->section('Prompt Templates');
        $templates = $this->promptTemplates->getTemplates();
        if (empty($templates)) {
            $io->text('No prompt templates configured');
        } else {
            $rows = [];
            foreach ($templates as $key => $name) {
                $template = $this->promptTemplates->getTemplate($key);
                $description = $template['description'] ?? '-';
                // Truncate description if too long
                if (\strlen($description) > 60) {
                    $description = substr($description, 0, 57).'...';
                }
                $rows[] = [
                    $key,
                    $name,
                    $description,
                ];
            }
            $io->table(
                ['Key', 'Name', 'Description'],
                $rows
            );
        }

        $io->newLine();
        $io->text('Use <info>bin/console debug:config xmon_ai_content</info> for full YAML configuration.');

        return Command::SUCCESS;
    }
}
