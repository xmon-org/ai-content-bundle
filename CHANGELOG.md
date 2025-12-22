# Changelog

All notable changes to `xmon-org/ai-content-bundle` will be documented in this file.

## 1.0.0 (2025-12-21)

### ✨ Features

* **admin:** añade AiImageContextInterface y show_bundle_credit ([9e47afa](https://github.com/xmon-org/ai-content-bundle/commit/9e47afa850fb83737fca1421b39feb12da1af3ca))
* **admin:** añade configuración de base_template personalizable ([28ee8bb](https://github.com/xmon-org/ai-content-bundle/commit/28ee8bba7a5e9014e00104d85ae0117f18309c3b))
* **admin:** añade UI de regeneración de imágenes para Sonata Admin ([90550f1](https://github.com/xmon-org/ai-content-bundle/commit/90550f1bc7b6f1abe4e8be7736fbe0c6d3b4af2b))
* añade sistema de estilos/presets configurable (Fase 4) ([f29b8b1](https://github.com/xmon-org/ai-content-bundle/commit/f29b8b10e9021ff37e04cde93e358f514ef07cc1))
* añade sistema de prompts configurables (Fase 5) ([b023942](https://github.com/xmon-org/ai-content-bundle/commit/b023942356e025f79bb6909e753358bee8aac94a))
* **di:** respeta prioridad configurada en providers de texto ([0443e4e](https://github.com/xmon-org/ai-content-bundle/commit/0443e4ed8d808e42d4afea78b045bd5ad076af9a))
* **entity:** añade AiPromptVariablesInterface para variables de prompt ([a7956d3](https://github.com/xmon-org/ai-content-bundle/commit/a7956d35655d064e224afc69a543448cd6f98cff))
* **history:** añade configuración de límite de imágenes en historial ([a93f4d5](https://github.com/xmon-org/ai-content-bundle/commit/a93f4d5c96984b128dc60338a562ca8d3a0bc726))
* **history:** añade modal de gestión cuando se alcanza límite ([e6a9e7c](https://github.com/xmon-org/ai-content-bundle/commit/e6a9e7c8065f5e420d47536f3965266be1392c24))
* initial bundle structure (Phase 1) ([7dbb63f](https://github.com/xmon-org/ai-content-bundle/commit/7dbb63face3f143a7f53ce1be5611262bd81855b))
* **media:** añade integración con SonataMedia ([fa16598](https://github.com/xmon-org/ai-content-bundle/commit/fa165985538b412e7d10151abec3c07cd950a37e))
* **prompts:** implementa sistema de variantes con matching por idioma ([215151c](https://github.com/xmon-org/ai-content-bundle/commit/215151c67530981e514f92bb03d4aa757e013f44))
* **provider:** añade API key, fallback_models y seed a Pollinations ([8a374d9](https://github.com/xmon-org/ai-content-bundle/commit/8a374d9b5a1204103d4b2644693640ea99defff5))
* **text:** añade proveedores de texto con sistema de fallback ([649e4bf](https://github.com/xmon-org/ai-content-bundle/commit/649e4bf4360c98c4842819e33f82a79ea25a1047))
* **ui:** añade página dedicada de imágenes IA con UX dinámica ([cef3438](https://github.com/xmon-org/ai-content-bundle/commit/cef34381a512619780e8904d3140fcfb952043fd))

### 🐛 Bug Fixes

* **admin:** corrige historial de prompts en generación IA ([522d36d](https://github.com/xmon-org/ai-content-bundle/commit/522d36d9a5e361b3b1cb1c55c50932dca198502e))
* **admin:** mejora preview de prompt en pantalla AI Image ([284343a](https://github.com/xmon-org/ai-content-bundle/commit/284343a9c2b86a0295aeb892d944e34f47b590d4))
* **ci:** ajusta PHPStan nivel 5 y excluye dependencias opcionales ([518216f](https://github.com/xmon-org/ai-content-bundle/commit/518216f2b4c9c13ca45907e425eccf149b4a64a6))
* **ci:** corrige configuración de PHPStan para bundle standalone ([4deeefa](https://github.com/xmon-org/ai-content-bundle/commit/4deeefadb85be226c66cd65a66430e1f55b7e829))
* **config:** preserva keys YAML con guiones en image_options y presets ([5cfc8b5](https://github.com/xmon-org/ai-content-bundle/commit/5cfc8b5883af646a482226e00a3ac1413e92aaf1))
* **phpstan:** corrige errores de análisis estático ([c10fb87](https://github.com/xmon-org/ai-content-bundle/commit/c10fb871d5a608769b8c628bfa08181e6d152e58))

### ♻️ Refactoring

* **admin:** simplifica templates y mueve assets a config ([07672f6](https://github.com/xmon-org/ai-content-bundle/commit/07672f69f03d17861c2c537b63d88128832c9554))
* **config:** cambia keys de snake_case a kebab-case ([bbe5387](https://github.com/xmon-org/ai-content-bundle/commit/bbe5387da0cf2c514847a61b0a731447cf05edd2))
* **di:** registra servicios condicionalmente según dependencias ([9af660e](https://github.com/xmon-org/ai-content-bundle/commit/9af660e1a70a9775c26bd63cdfd660b938473ef6))
* **provider:** mejora logging de requests en Pollinations ([896a2b8](https://github.com/xmon-org/ai-content-bundle/commit/896a2b8eece377230bee998681267428fd15c977))

### 📚 Documentation

* actualiza documentación con cambios de Pollinations ([7b3f3dd](https://github.com/xmon-org/ai-content-bundle/commit/7b3f3dde2d7eb5cb8a048d47ce4fe675bfa03cc4))
* actualiza roadmap, mueve entidades editables a futuras mejoras ([f6d19fa](https://github.com/xmon-org/ai-content-bundle/commit/f6d19fa70d4ceca8153e061611682dfb280622b5))
* add initial README with usage examples ([2f4da2d](https://github.com/xmon-org/ai-content-bundle/commit/2f4da2dcb5cd9eb5ea1f78be35084a36892ae161))
* fragmenta README en quickstart + docs/ estructurado ([f097f30](https://github.com/xmon-org/ai-content-bundle/commit/f097f307a3249934c5c701b58fdbb3e14c39bc07))
* update roadmap, move Together.ai fallback to optional ([87cec0f](https://github.com/xmon-org/ai-content-bundle/commit/87cec0f93c94036ebe9af2a691a75f0437bc8f8e))

### 👷 CI/CD

* añade Symfony 7.3 y 7.4 a la matriz de tests ([bf985c7](https://github.com/xmon-org/ai-content-bundle/commit/bf985c76bad245804a4778701cd8b733c5458dfc))
* añade versionado automático y herramientas de calidad ([c9d0776](https://github.com/xmon-org/ai-content-bundle/commit/c9d0776855bef8ca242a17a1fe78b86adad82241))

<!-- Este archivo se actualiza automáticamente con semantic-release -->
