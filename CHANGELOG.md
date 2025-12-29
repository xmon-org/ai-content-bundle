# Changelog

All notable changes to `xmon-org/ai-content-bundle` will be documented in this file.

## [1.11.0](https://github.com/xmon-org/ai-content-bundle/compare/1.10.2...1.11.0) (2025-12-29)

### ✨ Features

* **debug:** añade modo debug para testing sin consumir API ([7b73da1](https://github.com/xmon-org/ai-content-bundle/commit/7b73da140a2b14321a458f0a43be5fbdb962333b))

### 🐛 Bug Fixes

* **form:** render aiDebugMode checkbox in AiStyleConfigType template ([a00e5c7](https://github.com/xmon-org/ai-content-bundle/commit/a00e5c76d1ce6182ae89153b5f0540c48dbdb99a))

## [1.10.2](https://github.com/xmon-org/ai-content-bundle/compare/1.10.1...1.10.2) (2025-12-28)

### 🐛 Bug Fixes

* **task-config:** usa AiStyleService para modelo de imagen por defecto ([25f7183](https://github.com/xmon-org/ai-content-bundle/commit/25f7183b9c2efec30cc2eb86ae098366babb6263))

## [1.10.1](https://github.com/xmon-org/ai-content-bundle/compare/1.10.0...1.10.1) (2025-12-28)

### 🐛 Bug Fixes

* **image:** prevent real names and likeness phrases in PERSON anchor ([072bc9a](https://github.com/xmon-org/ai-content-bundle/commit/072bc9a1e4da8cfdae89179a2b4e053267865723))
* **image:** sanitize % in prompts to avoid Cloudflare WAF block ([9f65111](https://github.com/xmon-org/ai-content-bundle/commit/9f651110b8349f660e26c07f2b55486e4bdd618e))
* **providers:** detecta rate limits envueltos en HTTP 500 de Pollinations ([f1a2272](https://github.com/xmon-org/ai-content-bundle/commit/f1a2272eaa75bab0565c7bdc8c2b4a598b2088ef))

### 📚 Documentation

* actualiza documentación para arquitectura single-provider ([03fc7bf](https://github.com/xmon-org/ai-content-bundle/commit/03fc7bf7401fd6d20cf7ad88db5ad96fad64f6ac))

## [1.10.0](https://github.com/xmon-org/ai-content-bundle/compare/1.9.0...1.10.0) (2025-12-28)

### ✨ Features

* **debug:** muestra config de fallback y retries en xmon:ai:debug ([df89096](https://github.com/xmon-org/ai-content-bundle/commit/df89096a3d669987f164a91dd8b5b62f79fefaa5))
* **defaults:** configura modelos gratuitos por defecto ([efbe51e](https://github.com/xmon-org/ai-content-bundle/commit/efbe51ebb7d34a19a5fd8fd1de82b4fdc3d5fc81))

### ♻️ Refactoring

* **providers:** simplifica arquitectura a single provider con fallback por modelos ([2164829](https://github.com/xmon-org/ai-content-bundle/commit/21648298b285c5f9aeb36ddc7559f0331dd00f3e))

## [1.9.0](https://github.com/xmon-org/ai-content-bundle/compare/1.8.0...1.9.0) (2025-12-28)

### ✨ Features

* **admin:** añade lightbox fullscreen con mejoras UX/UI ([6a79b17](https://github.com/xmon-org/ai-content-bundle/commit/6a79b179cb256ff517b9cde27f7f69ec263bb325)), closes [#00a65a](https://github.com/xmon-org/ai-content-bundle/issues/00a65a)
* **admin:** añade modo sencillo/experto en AI Image Generator ([4d8148e](https://github.com/xmon-org/ai-content-bundle/commit/4d8148e8bfd081083e7b01da72e574841f83efd6))
* **config:** añade opción style_suffix para sufijo fijo en estilos ([3ce958c](https://github.com/xmon-org/ai-content-bundle/commit/3ce958cf24402c719aa96d025591d6ba3bfe646c))
* **entity:** añade campo aiStyleSuffix al trait y formulario ([ccb3e15](https://github.com/xmon-org/ai-content-bundle/commit/ccb3e1542b1d4c13d8ba03a3a2360ab98753d3e3))
* **form:** añade selector de modelo de imagen ([57c40dd](https://github.com/xmon-org/ai-content-bundle/commit/57c40dd20118a7eea5d6feb1423181b5223e66f9))
* **form:** muestra precio por imagen en vista previa del estilo ([6790806](https://github.com/xmon-org/ai-content-bundle/commit/6790806d5a75b760020dc09016c4caf44c1684f4))
* **provider:** añade getDefaultImageModel a style providers ([4a4816a](https://github.com/xmon-org/ai-content-bundle/commit/4a4816aaecd814ddad4f4d7d1ea215691dbba749))
* **service:** expone styleSuffix via ImageOptionsService ([ee0e355](https://github.com/xmon-org/ai-content-bundle/commit/ee0e35526688f950e62dcd852b5865ba7f278c93))

### 🐛 Bug Fixes

* **form:** añade event listener para actualizar preview de suffix en tiempo real ([cf065d5](https://github.com/xmon-org/ai-content-bundle/commit/cf065d541f948a4fddb782465681d03880eb9055))
* **form:** renderiza campo aiStyleSuffix en template ([4771292](https://github.com/xmon-org/ai-content-bundle/commit/4771292259eab8e26047b8b15108cfda1dc48a92))
* **models:** corrige precios incorrectos en ModelRegistryService ([476c84b](https://github.com/xmon-org/ai-content-bundle/commit/476c84b7401316732582356642c243411402163c))

### ♻️ Refactoring

* **admin:** separa mensajes de estado en prompt e imagen ([9509d14](https://github.com/xmon-org/ai-content-bundle/commit/9509d1482b85506de391bf7fe667ed2f8b6fd9be)), closes [#statusMessage](https://github.com/xmon-org/ai-content-bundle/issues/statusMessage) [#promptStatusMessage](https://github.com/xmon-org/ai-content-bundle/issues/promptStatusMessage) [#imageStatusMessage](https://github.com/xmon-org/ai-content-bundle/issues/imageStatusMessage)

### 📚 Documentation

* **admin:** documenta modo sencillo/experto en AI Image Generator ([54bfd7b](https://github.com/xmon-org/ai-content-bundle/commit/54bfd7b9da42ad65e816aecc1734cf9bd0196175))
* **config:** documenta opción style_suffix ([3243de3](https://github.com/xmon-org/ai-content-bundle/commit/3243de35ce817b9bbb958d1feddcb1237b65bec1))
* documenta selector de modelo de imagen ([92a8251](https://github.com/xmon-org/ai-content-bundle/commit/92a825143288974f002c45c58f5b0525ac26f375))

## [1.8.0](https://github.com/xmon-org/ai-content-bundle/compare/1.7.0...1.8.0) (2025-12-28)

### ✨ Features

* **config:** añade opción default_preset para fallback de estilos ([851f256](https://github.com/xmon-org/ai-content-bundle/commit/851f256b47844be39fbae016e5eb9c6702a48a18))
* **image:** add quality, negative_prompt, private, nofeed options ([9d0968b](https://github.com/xmon-org/ai-content-bundle/commit/9d0968b3738c0ccdeefe07da0226f16742ac238d))

### ♻️ Refactoring

* **api:** cambia claves de presets de español a inglés ([beda137](https://github.com/xmon-org/ai-content-bundle/commit/beda13729a0e16b4e51a281781768bba13c83678))
* **trait:** añade parámetro defaultPresetKey a buildStylePreview ([09cca0f](https://github.com/xmon-org/ai-content-bundle/commit/09cca0fb215e4daa840e117690f2ec0fa1623963))
* **ui:** usa etiquetas en inglés en template de configuración de estilos ([ea5b251](https://github.com/xmon-org/ai-content-bundle/commit/ea5b251a99f0e66b2e9e52a49a50a46324242d8f))

### 📚 Documentation

* actualiza documentación para default_preset y claves en inglés ([2dc8d68](https://github.com/xmon-org/ai-content-bundle/commit/2dc8d68e5e74a3a44ef79e22a327581157dbc92e))
* **pollinations:** añade schema OpenAPI 2025-12-28 ([69feddd](https://github.com/xmon-org/ai-content-bundle/commit/69fedddfecb23f030bca6ebb356be26273889d94))

## [1.7.0](https://github.com/xmon-org/ai-content-bundle/compare/1.6.1...1.7.0) (2025-12-27)

### ✨ Features

* **pollinations:** implementa dual endpoint mode para texto ([4d0410e](https://github.com/xmon-org/ai-content-bundle/commit/4d0410e31127d76bb16ab40cf1d2b14b1f0063dd))

### 🐛 Bug Fixes

* **pollinations:** actualiza endpoints a gen.pollinations.ai ([408bdcf](https://github.com/xmon-org/ai-content-bundle/commit/408bdcfd8cd7755034ca761867051bdb0dd70173))
* **pollinations:** corrige API de texto a formato GET con prompt en URL ([2c6a0ae](https://github.com/xmon-org/ai-content-bundle/commit/2c6a0ae36b5a409b789159c23af0f18d53df0c55))

## [1.6.2](https://github.com/xmon-org/ai-content-bundle/compare/1.6.1...1.6.2) (2025-12-27)

### 🐛 Bug Fixes

* **pollinations:** actualiza endpoints a gen.pollinations.ai ([408bdcf](https://github.com/xmon-org/ai-content-bundle/commit/408bdcfd8cd7755034ca761867051bdb0dd70173))
* **pollinations:** corrige API de texto a formato GET con prompt en URL ([2c6a0ae](https://github.com/xmon-org/ai-content-bundle/commit/2c6a0ae36b5a409b789159c23af0f18d53df0c55))

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
