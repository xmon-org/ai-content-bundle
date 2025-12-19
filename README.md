# xmon/ai-content-bundle

Symfony 7 bundle para generación de contenido con IA (texto e imágenes) con sistema de fallback entre proveedores.

## Requisitos

- PHP >= 8.2
- Symfony >= 7.0
- symfony/http-client

## Instalación

```bash
composer require xmon/ai-content-bundle
```

## Configuración

### 1. Variables de entorno

```bash
# .env
POLLINATIONS_API_KEY=tu_secret_key  # Opcional, sin key hay rate limits
```

### 2. Configuración del bundle

```yaml
# config/packages/xmon_ai_content.yaml
xmon_ai_content:
    image:
        providers:
            pollinations:
                enabled: true
                priority: 1
                api_key: '%env(POLLINATIONS_API_KEY)%'
                model: 'flux'
                timeout: 120
        defaults:
            width: 1280
            height: 720
            retries: 3
            retry_delay: 5
```

## Uso

### Generar una imagen

```php
use Xmon\AiContentBundle\Service\AiImageService;

class MyController
{
    public function __construct(
        private readonly AiImageService $aiImageService,
    ) {}

    public function generateImage(): Response
    {
        $result = $this->aiImageService->generate(
            prompt: 'A serene Japanese dojo with morning light',
            options: [
                'width' => 1280,
                'height' => 720,
            ]
        );

        // $result es un ImageResult con:
        // - getBytes(): raw image data
        // - getMimeType(): 'image/png', 'image/jpeg', etc.
        // - getProvider(): 'pollinations'
        // - getWidth(), getHeight()
        // - toBase64(), toDataUri()

        return new Response($result->getBytes(), 200, [
            'Content-Type' => $result->getMimeType(),
        ]);
    }
}
```

### Opciones de generación

```php
$result = $this->aiImageService->generate('prompt here', [
    'width' => 1280,           // Ancho en píxeles
    'height' => 720,           // Alto en píxeles
    'model' => 'flux',         // Modelo de IA
    'seed' => 12345,           // Seed para reproducibilidad
    'nologo' => true,          // Sin marca de agua (requiere API key)
    'enhance' => false,        // IA mejora el prompt automáticamente
    'provider' => 'pollinations', // Forzar proveedor específico
]);
```

## Proveedores disponibles

| Proveedor | Estado | Requiere API Key |
|-----------|--------|------------------|
| Pollinations | ✅ Implementado | Opcional (sin key = rate limits) |
| Together.ai | 🚧 Próximamente | Sí |

## Arquitectura

```
src/
├── Provider/
│   ├── ImageProviderInterface.php    # Contrato para proveedores
│   └── Image/
│       └── PollinationsImageProvider.php
├── Service/
│   └── AiImageService.php            # Orquestador con fallback
├── Model/
│   └── ImageResult.php               # DTO inmutable
└── Exception/
    └── AiProviderException.php       # Excepciones tipadas
```

## Desarrollo

Este bundle usa path repository durante desarrollo:

```json
// composer.json del proyecto
{
    "repositories": [
        { "type": "path", "url": "/packages/ai-content-bundle" }
    ],
    "require": {
        "xmon/ai-content-bundle": "@dev"
    }
}
```

## Roadmap

- [x] Fase 1: Estructura base + Pollinations
- [ ] Fase 2: Fallback con Together.ai
- [ ] Fase 3: Integración SonataMedia
- [ ] Fase 4: Proveedores de texto (Gemini, OpenRouter)
- [ ] Fase 5: Sistema de estilos/presets
- [ ] Fase 6: Entidades editables en Admin
- [ ] Fase 7: System prompts configurables
- [ ] Fase 8: UI de regeneración en Admin
- [ ] Fase 9: Migración proyecto Aikido
- [ ] Fase 10: Publicación en Packagist

## Licencia

MIT
