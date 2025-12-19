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
# Texto
XMON_AI_GEMINI_API_KEY=AIza...           # Gemini API (recomendado)
XMON_AI_OPENROUTER_API_KEY=sk-or-v1-...  # OpenRouter API (opcional)

# Imagen
XMON_AI_POLLINATIONS_API_KEY=tu_secret_key  # Opcional, sin key hay rate limits
```

### 2. Configuración del bundle

Todos los proveedores usan el mismo esquema de configuración:

```yaml
# config/packages/xmon_ai_content.yaml
xmon_ai_content:
    # Proveedores de texto (priority: mayor número = se intenta primero)
    text:
        providers:
            gemini:
                enabled: true
                priority: 100
                api_key: '%env(XMON_AI_GEMINI_API_KEY)%'
                model: 'gemini-2.0-flash-lite'
                fallback_models: []              # Modelos de respaldo
                timeout: 30
            openrouter:
                enabled: true
                priority: 50
                api_key: '%env(XMON_AI_OPENROUTER_API_KEY)%'
                model: 'google/gemini-2.0-flash-exp:free'
                fallback_models:                 # Si el modelo principal falla
                    - 'meta-llama/llama-3.3-70b-instruct:free'
                timeout: 90
            pollinations:
                enabled: true
                priority: 10
                model: 'openai'
                fallback_models: []
                timeout: 60
        defaults:
            retries: 2
            retry_delay: 3

    # Proveedores de imagen
    image:
        providers:
            pollinations:
                enabled: true
                priority: 100
                api_key: '%env(XMON_AI_POLLINATIONS_API_KEY)%'
                model: 'flux'
                timeout: 120
        defaults:
            width: 1280
            height: 720
            retries: 3
            retry_delay: 5

    # Solo si tienes SonataMedia instalado
    media:
        default_context: 'default'
        provider: 'sonata.media.provider.image'
```

### Esquema unificado de proveedores

| Campo | Tipo | Descripción |
|-------|------|-------------|
| `enabled` | bool | Activar/desactivar el proveedor |
| `priority` | int | Mayor número = se intenta primero |
| `api_key` | string | API key (si requiere) |
| `model` | string | Modelo principal |
| `fallback_models` | array | Modelos de respaldo (opcional) |
| `timeout` | int | Timeout en segundos |

### Activar/desactivar proveedores

Hay varias formas de controlar qué proveedores están activos:

#### Opción 1: Campo `enabled` (recomendado)

```yaml
xmon_ai_content:
    text:
        providers:
            gemini:
                enabled: true   # Activo
            openrouter:
                enabled: false  # Desactivado
            pollinations:
                enabled: true
```

#### Opción 2: Omitir el proveedor

Si no necesitas un proveedor, simplemente no lo incluyas:

```yaml
xmon_ai_content:
    text:
        providers:
            pollinations:
                enabled: true
                model: 'openai'
            # gemini y openrouter no aparecen = desactivados
```

#### Opción 3: Sin API key

Los proveedores que requieren API key (gemini, openrouter) reportan `isAvailable(): false` si no tienen key configurada, y el sistema los salta automáticamente:

```yaml
gemini:
    enabled: true
    api_key: null  # Sin key → isAvailable() = false → se salta
```

#### Comportamiento del sistema de fallback

| Situación | ¿Se registra? | ¿Se usa? |
|-----------|---------------|----------|
| `enabled: false` | No | No |
| `enabled: true` + sin API key | Sí | No (fallback al siguiente) |
| `enabled: true` + con API key | Sí | Sí (según prioridad) |
| No aparece en YAML | No | No |

El método recomendado es usar `enabled: false` porque es explícito y documenta la intención.

### Ejemplos de configuración

#### Solo Pollinations (configuración mínima)

```yaml
xmon_ai_content:
    text:
        providers:
            pollinations:
                enabled: true
    image:
        providers:
            pollinations:
                enabled: true
```

#### Gemini como primario con fallback

```yaml
xmon_ai_content:
    text:
        providers:
            gemini:
                enabled: true
                priority: 100
                api_key: '%env(XMON_AI_GEMINI_API_KEY)%'
            pollinations:
                enabled: true
                priority: 10  # Fallback si Gemini falla
```

#### OpenRouter con múltiples modelos gratuitos

```yaml
xmon_ai_content:
    text:
        providers:
            openrouter:
                enabled: true
                priority: 100
                api_key: '%env(XMON_AI_OPENROUTER_API_KEY)%'
                model: 'google/gemini-2.0-flash-exp:free'
                fallback_models:
                    - 'meta-llama/llama-3.3-70b-instruct:free'
                    - 'qwen/qwen3-235b-a22b:free'
                    - 'mistralai/mistral-small-3.1-24b-instruct:free'
```

## Uso

### Generar texto

```php
use Xmon\AiContentBundle\Service\AiTextService;

class MyService
{
    public function __construct(
        private readonly AiTextService $aiTextService,
    ) {}

    public function summarize(string $content): string
    {
        $result = $this->aiTextService->generate(
            systemPrompt: 'Eres un asistente que resume contenido. Responde en español.',
            userMessage: "Resume este texto: {$content}",
        );

        // $result es un TextResult con:
        // - getText(): el texto generado
        // - getProvider(): 'gemini', 'openrouter', 'pollinations'
        // - getModel(): modelo usado (ej: 'gemini-2.0-flash-lite')
        // - getPromptTokens(), getCompletionTokens()
        // - getFinishReason(): 'stop', 'length', etc.

        return $result->getText();
    }
}
```

### Opciones de generación de texto

```php
$result = $this->aiTextService->generate($systemPrompt, $userMessage, [
    'model' => 'gemini-2.0-flash',  // Modelo específico
    'temperature' => 0.7,           // Creatividad (0.0 - 1.0)
    'max_tokens' => 1500,           // Límite de tokens
    'provider' => 'gemini',         // Forzar proveedor específico
]);
```

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

### Opciones de generación de imagen

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

## Sistema de estilos y presets

El bundle incluye un sistema de opciones de imagen (estilos, composiciones, paletas, extras) y presets que combinan estas opciones.

### Servicios disponibles

- `ImageOptionsService`: Acceso a estilos, composiciones, paletas, extras y presets
- `PromptBuilder`: Construye prompts combinando subject + opciones

### Opciones por defecto

El bundle incluye opciones predefinidas que puedes sobrescribir o extender:

**Estilos artísticos:**
| Key | Label | Descripción |
|-----|-------|-------------|
| `sumi_e` | Sumi-e (tinta japonesa) | Estilo tradicional de pintura japonesa |
| `watercolor` | Acuarela | Pintura con bordes suaves |
| `oil_painting` | Óleo clásico | Estilo de pintura al óleo |
| `digital_art` | Arte digital | Estilo moderno digital |
| `photography` | Fotografía artística | Estilo fotográfico profesional |

**Composiciones:**
| Key | Label |
|-----|-------|
| `centered` | Centrada |
| `rule_of_thirds` | Regla de tercios |
| `negative_space` | Espacio negativo |
| `panoramic` | Panorámica |
| `close_up` | Primer plano |

**Paletas de color:**
| Key | Label |
|-----|-------|
| `monochrome` | Monocromo |
| `earth_tones` | Tonos tierra |
| `japanese_traditional` | Tradicional japonés |
| `muted` | Colores apagados |
| `high_contrast` | Alto contraste |

**Extras (modificadores):**
| Key | Label |
|-----|-------|
| `no_text` | Sin texto |
| `silhouettes` | Siluetas |
| `atmospheric` | Atmosférico |
| `dramatic_light` | Luz dramática |

### Presets predefinidos

Los presets combinan opciones en configuraciones listas para usar:

| Key | Nombre | Style | Composition | Palette | Extras |
|-----|--------|-------|-------------|---------|--------|
| `sumi_e_clasico` | Sumi-e Clásico | sumi_e | negative_space | monochrome | no_text, silhouettes, atmospheric |
| `zen_contemplativo` | Zen Contemplativo | sumi_e | centered | muted | no_text, atmospheric |
| `fotografia_aikido` | Fotografía Aikido | photography | rule_of_thirds | muted | dramatic_light |

### Uso del PromptBuilder

#### Con preset (modo simple)

```php
use Xmon\AiContentBundle\Service\PromptBuilder;
use Xmon\AiContentBundle\Service\AiImageService;

class MyService
{
    public function __construct(
        private readonly PromptBuilder $promptBuilder,
        private readonly AiImageService $aiImageService,
    ) {}

    public function generateImage(): ImageResult
    {
        // Construir prompt con preset
        $prompt = $this->promptBuilder->build(
            subject: 'aikidoka meditating in a traditional dojo',
            options: ['preset' => 'sumi_e_clasico']
        );

        // Resultado:
        // "aikidoka meditating in a traditional dojo, sumi-e Japanese ink wash
        //  painting, elegant brushstrokes, traditional, generous negative space,
        //  minimalist, breathing room, monochromatic color scheme, no text, ..."

        return $this->aiImageService->generate($prompt);
    }
}
```

#### Con opciones individuales (modo avanzado)

```php
$prompt = $this->promptBuilder->build(
    subject: 'serene bamboo forest at dawn',
    options: [
        'style' => 'watercolor',
        'composition' => 'panoramic',
        'palette' => 'earth_tones',
        'extras' => ['atmospheric', 'no_text'],
    ]
);
```

#### Preset + override de opciones

Puedes usar un preset como base y sobrescribir opciones específicas:

```php
$prompt = $this->promptBuilder->build(
    subject: 'aikido seminar group photo',
    options: [
        'preset' => 'zen_contemplativo',
        'composition' => 'rule_of_thirds', // Override del preset
    ]
);
```

#### Texto libre con custom_prompt

Además de los extras predefinidos (multiselect), puedes añadir texto libre al final del prompt:

```php
$prompt = $this->promptBuilder->build(
    subject: 'aikidoka meditating in dojo',
    options: [
        'preset' => 'sumi_e_clasico',
        'extras' => ['no_text', 'atmospheric'],       // Extras predefinidos
        'custom_prompt' => 'cinematic 16:9 ratio, 4k resolution',  // Texto libre
    ]
);

// Resultado:
// "aikidoka meditating in dojo, sumi-e Japanese ink wash painting, ...,
//  no text, atmospheric perspective, cinematic 16:9 ratio, 4k resolution"
```

**Casos de uso para `custom_prompt`:**
- Input en el Admin para añadir modificadores al vuelo
- Configuración fija en código para ciertos contextos
- Parámetros técnicos (aspect ratio, resolución, etc.)

### Obtener opciones para UI

`ImageOptionsService` proporciona métodos para poblar selects en formularios:

```php
use Xmon\AiContentBundle\Service\ImageOptionsService;

class MyAdmin
{
    public function __construct(
        private readonly ImageOptionsService $imageOptions,
    ) {}

    public function getFormChoices(): array
    {
        return [
            'styles' => $this->imageOptions->getStyles(),
            // ['sumi_e' => 'Sumi-e (tinta japonesa)', 'watercolor' => 'Acuarela', ...]

            'compositions' => $this->imageOptions->getCompositions(),
            'palettes' => $this->imageOptions->getPalettes(),
            'extras' => $this->imageOptions->getExtras(),
            'presets' => $this->imageOptions->getPresets(),
        ];
    }
}
```

### Personalizar opciones en tu proyecto

Las opciones del bundle se **fusionan** con las tuyas automáticamente. Tus opciones se añaden a los defaults, y si usas la misma key puedes sobrescribirlos.

#### Añadir opciones (merge automático)

```yaml
# config/packages/xmon_ai_content.yaml
xmon_ai_content:
    image_options:
        # Añadir estilos artísticos
        styles:
            ukiyo_e:
                label: 'Ukiyo-e'
                prompt: 'ukiyo-e Japanese woodblock print style, bold outlines, flat colors'
            manga:
                label: 'Manga'
                prompt: 'manga art style, anime aesthetic, bold lines'

        # Añadir composiciones
        compositions:
            diagonal:
                label: 'Diagonal'
                prompt: 'diagonal composition, dynamic angles, leading lines'
            symmetrical:
                label: 'Simétrica'
                prompt: 'perfect symmetry, mirror balance, architectural precision'

        # Añadir paletas de color
        palettes:
            neon:
                label: 'Neón'
                prompt: 'neon colors, cyberpunk palette, glowing accents'
            pastel:
                label: 'Pastel'
                prompt: 'soft pastel colors, gentle hues, dreamy tones'

        # Añadir extras/modificadores
        extras:
            cinematic:
                label: 'Cinematográfico'
                prompt: 'cinematic lighting, movie scene, dramatic atmosphere'
            vintage:
                label: 'Vintage'
                prompt: 'vintage film look, grain, faded colors, retro aesthetic'

    # Añadir presets (combinaciones predefinidas)
    presets:
        dojo_moderno:
            name: 'Dojo Moderno'
            style: 'photography'
            composition: 'diagonal'
            palette: 'high_contrast'
            extras: ['cinematic', 'no_text']
        manga_action:
            name: 'Manga Acción'
            style: 'manga'
            composition: 'diagonal'
            palette: 'neon'
            extras: ['dramatic_light']
```

**Resultado**: Todas las opciones se fusionan con los defaults del bundle.

#### Sobrescribir un default

Usa la misma key para modificar un default:

```yaml
xmon_ai_content:
    image_options:
        styles:
            # Sobrescribir el estilo photography del bundle
            photography:
                label: 'Fotografía profesional'
                prompt: 'professional studio photography, product shot, clean background'
```

#### Deshabilitar defaults específicos

Si no quieres usar algunos defaults del bundle, puedes deshabilitarlos:

```yaml
xmon_ai_content:
    image_options:
        disable_defaults:
            styles: ['oil_painting', 'digital_art']
            compositions: ['panoramic']
            palettes: ['high_contrast']
            extras: ['silhouettes']

    # Para presets, usa esta opción a nivel raíz
    disable_preset_defaults: ['zen_contemplativo']
```

**Resultado**: Los estilos, composiciones, etc. listados se eliminan de los disponibles.

> **Importante**: Si deshabilitas una opción que está siendo usada por un preset, ese preset se deshabilita automáticamente. Por ejemplo, si deshabilitas `sumi_e`, los presets `sumi_e_clasico` y `zen_contemplativo` también se desactivan.

> **Tip**: Usa `bin/console xmon:ai:debug` para ver todas las opciones configuradas actualmente.

### Validación

El `PromptBuilder` valida que las opciones existan y lanza `AiProviderException` si no:

```php
try {
    $prompt = $this->promptBuilder->build('subject', [
        'preset' => 'invalid_preset'
    ]);
} catch (AiProviderException $e) {
    // "Unknown preset: invalid_preset"
}
```

## Sistema de fallback

El bundle implementa un sistema de fallback automático entre proveedores:

```
┌─────────────────────────────────────────────────────────────┐
│                    AiTextService                            │
│                                                             │
│  generate(systemPrompt, userMessage)                        │
│         │                                                   │
│         ▼                                                   │
│  ┌─────────────┐    ┌─────────────┐    ┌─────────────┐     │
│  │   Gemini    │───▶│ OpenRouter  │───▶│Pollinations │     │
│  │ (priority   │    │ (priority   │    │ (priority   │     │
│  │   100)      │    │   50)       │    │   10)       │     │
│  └─────────────┘    └─────────────┘    └─────────────┘     │
│         │                 │                   │             │
│         ▼                 ▼                   ▼             │
│      ¿Error?           ¿Error?            ¿Error?           │
│         │                 │                   │             │
│    No──▶│ TextResult  No──▶│ TextResult   No──▶│TextResult │
│    Sí──▶│ Siguiente   Sí──▶│ Siguiente    Sí──▶│Exception  │
└─────────────────────────────────────────────────────────────┘
```

1. El sistema ordena los proveedores por prioridad (mayor primero)
2. Intenta el primer proveedor disponible (`isAvailable() = true`)
3. Si falla, pasa al siguiente
4. Si todos fallan, lanza `AllProvidersFailedException`

## Proveedores disponibles

### Texto

| Proveedor | Estado | Requiere API Key | Prioridad por defecto | Notas |
|-----------|--------|------------------|----------------------|-------|
| Gemini | Implementado | Sí | 100 | Recomendado, rápido y gratuito con límites |
| OpenRouter | Implementado | Sí | 50 | Múltiples modelos, fallback interno |
| Pollinations | Implementado | No | 10 | Siempre disponible, fallback final |

### Imagen

| Proveedor | Estado | Requiere API Key | Notas |
|-----------|--------|------------------|-------|
| Pollinations | Implementado | Opcional | Sin key = rate limits |

## Arquitectura

```
xmon/ai-content-bundle/
├── composer.json
├── README.md
├── config/
│   ├── services.yaml                 # Servicios core imagen (siempre)
│   ├── services_text.yaml            # Proveedores de texto (siempre)
│   └── services_media.yaml           # SonataMedia (si está instalado)
└── src/
    ├── XmonAiContentBundle.php       # Bundle class
    ├── DependencyInjection/
    │   ├── Configuration.php         # Configuración YAML validada
    │   └── XmonAiContentExtension.php # Carga condicional de servicios
    ├── Provider/
    │   ├── ImageProviderInterface.php
    │   ├── TextProviderInterface.php  # Con #[AutoconfigureTag]
    │   ├── Image/
    │   │   └── PollinationsImageProvider.php
    │   └── Text/
    │       ├── GeminiTextProvider.php
    │       ├── OpenRouterTextProvider.php
    │       └── PollinationsTextProvider.php
    ├── Service/
    │   ├── AiImageService.php        # Orquestador imagen con fallback
    │   ├── AiTextService.php         # Orquestador texto con fallback
    │   ├── ImageOptionsService.php   # Gestión de estilos/presets
    │   ├── PromptBuilder.php         # Construye prompts con opciones
    │   └── MediaStorageService.php   # SonataMedia (condicional)
    ├── Model/
    │   ├── ImageResult.php           # DTO inmutable
    │   └── TextResult.php            # DTO inmutable
    ├── Command/
    │   └── DebugConfigCommand.php    # xmon:ai:debug
    └── Exception/
        └── AiProviderException.php   # Excepciones tipadas
```

## Proveedores personalizados

Puedes añadir tus propios proveedores de texto implementando `TextProviderInterface`. El bundle los detecta automáticamente gracias a `#[AutoconfigureTag]`.

### Crear un provider personalizado

```php
// src/Provider/AnthropicTextProvider.php
namespace App\Provider;

use Symfony\Contracts\HttpClient\HttpClientInterface;
use Xmon\AiContentBundle\Model\TextResult;
use Xmon\AiContentBundle\Provider\TextProviderInterface;

class AnthropicTextProvider implements TextProviderInterface
{
    public function __construct(
        private readonly HttpClientInterface $httpClient,
        private readonly ?string $apiKey = null,
        private readonly int $priority = 80,
    ) {}

    public function getName(): string
    {
        return 'anthropic';
    }

    public function isAvailable(): bool
    {
        return !empty($this->apiKey);
    }

    public function getPriority(): int
    {
        return $this->priority; // Entre Gemini (100) y OpenRouter (50)
    }

    public function generate(string $systemPrompt, string $userMessage, array $options = []): TextResult
    {
        // Tu implementación...
        return new TextResult(
            text: $response,
            provider: $this->getName(),
            model: 'claude-3-haiku',
        );
    }
}
```

### Registrar el provider

Con `autoconfigure: true` (por defecto en Symfony), el provider se registra automáticamente. Solo necesitas configurar los argumentos:

```yaml
# config/services.yaml
App\Provider\AnthropicTextProvider:
    arguments:
        $apiKey: '%env(XMON_AI_ANTHROPIC_API_KEY)%'
        $priority: 80
    tags:
        - { name: 'xmon_ai_content.text_provider', priority: 80 }
```

El provider aparecerá automáticamente en la cadena de fallback según su prioridad.

## Desarrollo

Este bundle usa path repository durante desarrollo:

```json
// composer.json del proyecto
{
    "repositories": [
        { "type": "path", "url": "../packages/ai-content-bundle" }
    ],
    "require": {
        "xmon/ai-content-bundle": "@dev"
    }
}
```

### Comandos útiles

```bash
# Ver resumen de configuración del bundle
bin/console xmon:ai:debug

# Limpiar caché después de cambios en el bundle
bin/console cache:clear

# Ver proveedores registrados
bin/console debug:container --tag=xmon_ai_content.text_provider

# Ver configuración YAML completa del bundle
bin/console debug:config xmon_ai_content
```

### Comando xmon:ai:debug

El bundle incluye un comando de diagnóstico que muestra:

- Proveedores de texto disponibles
- Proveedores de imagen disponibles
- Estilos, composiciones, paletas y extras configurados
- Presets con sus opciones

```
$ bin/console xmon:ai:debug

xmon/ai-content-bundle Configuration
====================================

Text Providers
--------------
 ✓   gemini
 ✓   openrouter
 ✓   pollinations

Image Providers
---------------
 ✓   pollinations

Styles
------
 sumi_e         Sumi-e (tinta japonesa)
 watercolor     Acuarela
 ...

Presets
-------
 sumi_e_clasico   Sumi-e Clásico   sumi_e   negative_space   monochrome   ...
```

## Roadmap

- [x] Fase 1: Estructura base + Pollinations
- [x] Fase 2: Integración SonataMedia
- [x] Fase 3: Proveedores de texto (Gemini, OpenRouter, Pollinations)
- [x] Fase 4: Sistema de estilos/presets (ImageOptionsService, PromptBuilder)
- [ ] Fase 5: Entidades editables en Admin
- [ ] Fase 6: System prompts configurables
- [ ] Fase 7: UI de regeneración en Admin
- [ ] Fase 8: Migración proyecto Aikido
- [ ] Fase 9: Publicación en Packagist

## Licencia

MIT
