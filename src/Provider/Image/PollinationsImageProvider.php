<?php

declare(strict_types=1);

namespace Xmon\AiContentBundle\Provider\Image;

use Psr\Log\LoggerInterface;
use Symfony\Contracts\HttpClient\Exception\ExceptionInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Xmon\AiContentBundle\Exception\AiProviderException;
use Xmon\AiContentBundle\Model\ImageResult;
use Xmon\AiContentBundle\Provider\ImageProviderInterface;

class PollinationsImageProvider implements ImageProviderInterface
{
    private const BASE_URL = 'https://gen.pollinations.ai/image/';
    private const PROVIDER_NAME = 'pollinations';

    public function __construct(
        private readonly HttpClientInterface $httpClient,
        private readonly ?LoggerInterface $logger = null,
        private readonly ?string $apiKey = null,
        private readonly string $model = 'flux',
        private readonly int $timeout = 120,
        private readonly int $defaultWidth = 1280,
        private readonly int $defaultHeight = 720,
        private readonly string $quality = 'high',
        private readonly string $negativePrompt = 'worst quality, blurry, text, letters, watermark, human faces, detailed faces',
        private readonly bool $private = true,
        private readonly bool $nofeed = true,
    ) {
    }

    public function getName(): string
    {
        return self::PROVIDER_NAME;
    }

    public function isAvailable(): bool
    {
        // Pollinations works without API key (with rate limits)
        // With API key, no rate limits
        return true;
    }

    public function generate(string $prompt, array $options = []): ImageResult
    {
        $width = $options['width'] ?? $this->defaultWidth;
        $height = $options['height'] ?? $this->defaultHeight;
        $model = $options['model'] ?? $this->model;
        $seed = $options['seed'] ?? null;
        $nologo = $options['nologo'] ?? ($this->apiKey !== null);
        $enhance = $options['enhance'] ?? false;
        $safe = $options['safe'] ?? false;
        $quality = $options['quality'] ?? $this->quality;
        $negativePrompt = $options['negative_prompt'] ?? $this->negativePrompt;
        $private = $options['private'] ?? $this->private;
        $nofeed = $options['nofeed'] ?? $this->nofeed;

        // Build URL with encoded prompt
        $encodedPrompt = $this->encodePrompt($prompt);
        $url = self::BASE_URL.$encodedPrompt;

        // Build query parameters
        $params = [
            'width' => $width,
            'height' => $height,
            'model' => $model,
            'quality' => $quality,
            'nologo' => $nologo ? 'true' : 'false',
            'enhance' => $enhance ? 'true' : 'false',
            'safe' => $safe ? 'true' : 'false',
            'private' => $private ? 'true' : 'false',
            'nofeed' => $nofeed ? 'true' : 'false',
        ];

        if ($seed !== null) {
            $params['seed'] = $seed;
        }

        if (!empty($negativePrompt)) {
            $params['negative_prompt'] = $negativePrompt;
        }

        $url .= '?'.http_build_query($params);

        $this->logger?->info('[Pollinations] Generating image', [
            'model' => $model,
            'width' => $width,
            'height' => $height,
            'quality' => $quality,
            'seed' => $seed,
            'private' => $private,
            'has_negative_prompt' => !empty($negativePrompt),
            'prompt_length' => \strlen($prompt),
            'prompt' => $prompt,
        ]);

        // Build request options
        $requestOptions = [
            'timeout' => $this->timeout,
        ];

        // Add Authorization header if API key available (required for premium models)
        if ($this->apiKey !== null) {
            $requestOptions['headers'] = [
                'Authorization' => 'Bearer '.$this->apiKey,
            ];
        }

        try {
            $response = $this->httpClient->request('GET', $url, $requestOptions);

            $statusCode = $response->getStatusCode();

            if ($statusCode !== 200) {
                throw AiProviderException::httpError(self::PROVIDER_NAME, $statusCode, $response->getContent(false));
            }

            $content = $response->getContent();
            $contentType = $response->getHeaders()['content-type'][0] ?? 'image/png';

            // Normalize content type
            $mimeType = $this->normalizeMimeType($contentType);

            $this->logger?->info('[Pollinations] Image generated successfully', [
                'size' => \strlen($content),
                'mime_type' => $mimeType,
            ]);

            return new ImageResult(
                bytes: $content,
                mimeType: $mimeType,
                provider: self::PROVIDER_NAME,
                width: $width,
                height: $height,
                model: $model,
                seed: $seed,
                prompt: $prompt,
            );
        } catch (ExceptionInterface $e) {
            $this->logger?->error('[Pollinations] Request failed', [
                'error' => $e->getMessage(),
            ]);

            if (str_contains($e->getMessage(), 'timeout')) {
                throw AiProviderException::timeout(self::PROVIDER_NAME, $this->timeout);
            }

            throw AiProviderException::connectionError(self::PROVIDER_NAME, $e->getMessage());
        }
    }

    /**
     * Encode prompt for URL (spaces as %20, handle special characters).
     */
    private function encodePrompt(string $prompt): string
    {
        // Replace multiple spaces with single space
        $prompt = preg_replace('/\s+/', ' ', trim($prompt));

        // URL encode the entire prompt
        return rawurlencode($prompt);
    }

    /**
     * Normalize MIME type from response header.
     */
    private function normalizeMimeType(string $contentType): string
    {
        // Remove charset and other parameters
        $mimeType = explode(';', $contentType)[0];
        $mimeType = trim($mimeType);

        return match (true) {
            str_contains($mimeType, 'jpeg'), str_contains($mimeType, 'jpg') => 'image/jpeg',
            str_contains($mimeType, 'png') => 'image/png',
            str_contains($mimeType, 'webp') => 'image/webp',
            str_contains($mimeType, 'gif') => 'image/gif',
            default => 'image/png',
        };
    }
}
