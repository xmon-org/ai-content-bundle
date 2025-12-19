<?php

declare(strict_types=1);

namespace Xmon\AiContentBundle\Command;

use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Xmon\AiContentBundle\Service\AiImageService;
use Xmon\AiContentBundle\Service\AiTextService;
use Xmon\AiContentBundle\Service\ImageOptionsService;

#[AsCommand(
    name: 'xmon:ai:debug',
    description: 'Display the current configuration of xmon/ai-content-bundle',
)]
class DebugConfigCommand extends Command
{
    public function __construct(
        private readonly AiTextService $textService,
        private readonly AiImageService $imageService,
        private readonly ImageOptionsService $imageOptions,
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $io->title('xmon/ai-content-bundle Configuration');

        // Text Providers
        $io->section('Text Providers');
        $textProviders = $this->textService->getAvailableProviders();
        if (empty($textProviders)) {
            $io->warning('No text providers available');
        } else {
            $rows = [];
            foreach ($textProviders as $name) {
                $rows[] = [
                    '<info>✓</info>',
                    $name,
                ];
            }
            $io->table(['', 'Provider'], $rows);
        }

        // Image Providers
        $io->section('Image Providers');
        $imageProviders = $this->imageService->getAvailableProviders();
        if (empty($imageProviders)) {
            $io->warning('No image providers available');
        } else {
            $rows = [];
            foreach ($imageProviders as $name) {
                $rows[] = [
                    '<info>✓</info>',
                    $name,
                ];
            }
            $io->table(['', 'Provider'], $rows);
        }

        // Styles
        $io->section('Styles');
        $styles = $this->imageOptions->getStyles();
        if (empty($styles)) {
            $io->text('No styles configured');
        } else {
            $io->table(
                ['Key', 'Label'],
                array_map(fn($k, $v) => [$k, $v], array_keys($styles), array_values($styles))
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
                array_map(fn($k, $v) => [$k, $v], array_keys($compositions), array_values($compositions))
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
                array_map(fn($k, $v) => [$k, $v], array_keys($palettes), array_values($palettes))
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
                array_map(fn($k, $v) => [$k, $v], array_keys($extras), array_values($extras))
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

        $io->newLine();
        $io->text('Use <info>bin/console debug:config xmon_ai_content</info> for full YAML configuration.');

        return Command::SUCCESS;
    }
}
