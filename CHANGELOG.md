# Changelog

All notable changes to `xmon-org/ai-content-bundle` will be documented in this file.

## [1.6.2](https://github.com/xmon-org/ai-content-bundle/compare/1.6.1...1.6.2) (2025-12-27)

### 🐛 Bug Fixes

* **pollinations:** actualiza endpoints a gen.pollinations.ai ([408bdcf](https://github.com/xmon-org/ai-content-bundle/commit/408bdcfd8cd7755034ca761867051bdb0dd70173))

## [1.6.1](https://github.com/xmon-org/ai-content-bundle/compare/1.6.0...1.6.1) (2025-12-27)

### ♻️ Refactoring

* **command:** simplifica debug para arquitectura single-provider ([9c2acf0](https://github.com/xmon-org/ai-content-bundle/commit/9c2acf07ca8f051131ae97c58e075bdc5f84ad30))

## [1.6.0](https://github.com/xmon-org/ai-content-bundle/compare/1.5.0...1.6.0) (2025-12-27)

### ✨ Features

* **controller:** integra ImageSubjectGenerator en AbstractAiImageController ([b13b753](https://github.com/xmon-org/ai-content-bundle/commit/b13b7532bb8f0781d62ee2572cec3cb87ef3924c))

## [1.5.0](https://github.com/xmon-org/ai-content-bundle/compare/1.4.1...1.5.0) (2025-12-27)

### ✨ Features

* **image:** añade ImageSubjectGenerator con sistema two-step ([860083e](https://github.com/xmon-org/ai-content-bundle/commit/860083ed7555f240e94e4d30dfc76fb6d2f67fc7))

## [1.4.1](https://github.com/xmon-org/ai-content-bundle/compare/1.4.0...1.4.1) (2025-12-27)

### 🐛 Bug Fixes

* **docs:** actualiza docs y excepciones para v1.4 ([c5aec58](https://github.com/xmon-org/ai-content-bundle/commit/c5aec58d65310b14cd55d39a459eff199a5c8f24))

### 📚 Documentation

* corrige inconsistencias entre documentos ([60695f8](https://github.com/xmon-org/ai-content-bundle/commit/60695f86fe4de588cc8dc848e6f0831db49d25be))
* **readme:** añade presencia de Pollinations.ai como gateway ([e8aa80a](https://github.com/xmon-org/ai-content-bundle/commit/e8aa80a384e2bd7d41d870b8758fea07ebd389ab))

## [1.4.0](https://github.com/xmon-org/ai-content-bundle/compare/1.3.0...1.4.0) (2025-12-26)

### ✨ Features

* **providers:** simplifica arquitectura a Pollinations único + TaskTypes ([feb371f](https://github.com/xmon-org/ai-content-bundle/commit/feb371f0aa7b9148133a31ad16e2c34ea0df9a69))

### 🐛 Bug Fixes

* **models:** actualiza modelos con datos reales de Pollinations API ([f0449e7](https://github.com/xmon-org/ai-content-bundle/commit/f0449e74bf35cdf2819639b178d9bcf203d92f04))

## [1.3.0](https://github.com/xmon-org/ai-content-bundle/compare/1.2.0...1.3.0) (2025-12-24)

### ✨ Features

* **image:** añade resolución de estilos por entidad ([0d63f13](https://github.com/xmon-org/ai-content-bundle/commit/0d63f1381a0af5389aef59ea657e3d056519e9f0))
* **image:** añade sufijo configurable a prompts de estilo ([aac0e57](https://github.com/xmon-org/ai-content-bundle/commit/aac0e57da1484f1e4af872bd3dddf176260c52c7))
* **templates:** implementa optgroup en selectores de estilo ([2850bb7](https://github.com/xmon-org/ai-content-bundle/commit/2850bb75322cb241f6776739a95e58f297d2a6af))
* **templates:** muestra sufijo de estilo en preview de imagen ([6245585](https://github.com/xmon-org/ai-content-bundle/commit/624558520902feb074f1dbd9b42aad46f43a93e4))

### ♻️ Refactoring

* **form:** normaliza presets a inglés y registra form type ([31a8100](https://github.com/xmon-org/ai-content-bundle/commit/31a810081678646204b2ba8fd7cebdd61c3f1677))

### 📚 Documentation

* **image-options:** documenta métodos *GroupedByKey para HTML selects ([d8269c5](https://github.com/xmon-org/ai-content-bundle/commit/d8269c502b26d5b7258c2628d37832a35da3a61a))

## [1.2.0](https://github.com/xmon-org/ai-content-bundle/compare/1.1.0...1.2.0) (2025-12-24)

### ✨ Features

* **image:** añade optgroup y carga automática de opciones ([8cec638](https://github.com/xmon-org/ai-content-bundle/commit/8cec6387419fa73a2b9a7e2b6532f036ddf46e50))

### 🐛 Bug Fixes

* **form:** usa getPresetsForForm para resolver prompts en presets ([52b272d](https://github.com/xmon-org/ai-content-bundle/commit/52b272d17dacf7d992e5fdc8aecf9bb47891e300))
* **image:** corrige lógica de grupo en formatGrouped ([825746c](https://github.com/xmon-org/ai-content-bundle/commit/825746c76e5f8a4399d2d18d0f92ba63e23c8ebe))

### 📚 Documentation

* corrige enlace del badge de licencia en README ([ac807af](https://github.com/xmon-org/ai-content-bundle/commit/ac807af5c17c2e1496a4aa053771faf5185ff306))
* optimize phpstan config and enhance development workflow ([adc06ee](https://github.com/xmon-org/ai-content-bundle/commit/adc06ee295f5f41c86a43c5d9c32294d2c3fef4e))

## [1.1.0](https://github.com/xmon-org/ai-content-bundle/compare/1.0.0...1.1.0) (2025-12-22)

### ✨ Features

* **admin:** usa resolveGlobalStyle() para preview de estilos ([5c3cb13](https://github.com/xmon-org/ai-content-bundle/commit/5c3cb13b12c763cf5ba86b48c450d9dd83069de6))
* **prompts:** añade soporte regex en variant_keywords ([33908b7](https://github.com/xmon-org/ai-content-bundle/commit/33908b7426f855f65422aa4c9d7f8f70e93e72b2))
* **styles:** añade sistema de configuración de estilos IA ([adf867c](https://github.com/xmon-org/ai-content-bundle/commit/adf867c3e55165c2656e934ca60b9cb7c35a3610))

### ♻️ Refactoring

* mueve recursos de src/Resources a templates/ y public/ ([6c78828](https://github.com/xmon-org/ai-content-bundle/commit/6c7882844f27a7c6bde3e84430d5376a1407af04))

### 📚 Documentation

* documenta AiStyleConfigType y actualiza guías ([6f7ead8](https://github.com/xmon-org/ai-content-bundle/commit/6f7ead863c0f10428380287707fa05ac921cdc0e))

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
