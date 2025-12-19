# xmon/ai-content-bundle

Symfony 7 bundle para generación de contenido con IA (texto e imágenes) con sistema de fallback entre proveedores.

## Requisitos

- PHP >= 8.2
- Symfony >= 7.0
- symfony/http-client

## Dependencias opcionales

El bundle detecta automáticamente qué dependencias opcionales están instaladas y habilita las funcionalidades correspondientes:

| Dependencia | Funcionalidad | Servicio habilitado |
|-------------|---------------|---------------------|
| `sonata-project/media-bundle` | Guardar imágenes en SonataMedia | `MediaStorageService` |
| `sonata-project/admin-bundle` | UI de administración | Admin classes (próximamente) |

**Sin SonataMedia instalado**: El bundle funciona perfectamente para generar imágenes. Puedes guardarlas donde quieras (disco, S3, etc.) usando `ImageResult::getBytes()`.

**Con SonataMedia instalado**: Se habilita `MediaStorageService` para guardar directamente en SonataMedia.

## Instalación

```bash
composer require xmon/ai-content-bundle
```

Para habilitar integración con SonataMedia:
```bash
composer require sonata-project/media-bundle
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

    # Solo si tienes SonataMedia instalado
    media:
        default_context: 'default'        # SonataMedia context
        provider: 'sonata.media.provider.image'
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

### Guardar en SonataMedia

> Requiere `sonata-project/media-bundle` instalado

```php
use Xmon\AiContentBundle\Service\AiImageService;
use Xmon\AiContentBundle\Service\MediaStorageService;

class MyService
{
    public function __construct(
        private readonly AiImageService $aiImageService,
        private readonly MediaStorageService $mediaStorageService,
    ) {}

    public function generateForNoticia(): MediaInterface
    {
        $result = $this->aiImageService->generate('A serene dojo');

        // Guardar en el context de la entidad destino
        return $this->mediaStorageService->save(
            imageResult: $result,
            filename: 'noticia-ai-image',   // Opcional (genera automático)
            context: 'noticias',            // Context de SonataMedia de la entidad
        );
    }

    public function generateForEvento(): MediaInterface
    {
        $result = $this->aiImageService->generate('Aikido seminar');

        // Cada entidad puede usar su propio context
        return $this->mediaStorageService->save(
            imageResult: $result,
            context: 'eventos',
        );
    }
}
```

**Nota sobre contexts**: Usa el mismo context que la entidad destino para que los formatos de imagen (thumbnails, etc.) sean consistentes. El `default_context` de la configuración es solo un fallback.

### Guardar manualmente (sin SonataMedia)

```php
$result = $this->aiImageService->generate('A serene dojo');

// Guardar a disco
file_put_contents('image.' . $result->getExtension(), $result->getBytes());

// O enviar a S3, otro servicio, etc.
$s3->putObject([
    'Body' => $result->getBytes(),
    'ContentType' => $result->getMimeType(),
]);
```

## Proveedores disponibles

| Proveedor | Estado | Requiere API Key |
|-----------|--------|------------------|
| Pollinations | ✅ Implementado | Opcional (sin key = rate limits) |

## Arquitectura

```
xmon/ai-content-bundle/
├── composer.json
├── README.md
├── config/
│   ├── services.yaml                 # Servicios core (siempre)
│   └── services_media.yaml           # SonataMedia (si está instalado)
└── src/
    ├── XmonAiContentBundle.php       # Bundle class
    ├── DependencyInjection/
    │   ├── Configuration.php         # Configuración YAML validada
    │   └── XmonAiContentExtension.php # Carga condicional de servicios
    ├── Provider/
    │   ├── ImageProviderInterface.php # Contrato para proveedores
    │   └── Image/
    │       └── PollinationsImageProvider.php
    ├── Service/
    │   ├── AiImageService.php        # Orquestador con fallback
    │   └── MediaStorageService.php   # SonataMedia (condicional)
    ├── Model/
    │   └── ImageResult.php           # DTO inmutable
    └── Exception/
        └── AiProviderException.php   # Excepciones tipadas
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
- [x] Fase 2: Integración SonataMedia
- [ ] Fase 3: Proveedores de texto (Gemini, OpenRouter)
- [ ] Fase 4: Sistema de estilos/presets
- [ ] Fase 5: Entidades editables en Admin
- [ ] Fase 6: System prompts configurables
- [ ] Fase 7: UI de regeneración en Admin
- [ ] Fase 8: Migración proyecto Aikido
- [ ] Fase 9: Publicación en Packagist
- [ ] Extra: Fallback con Together.ai (opcional)

## Licencia

MIT
