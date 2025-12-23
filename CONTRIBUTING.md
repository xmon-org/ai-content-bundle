# Contributing to xmon-org/ai-content-bundle

¡Gracias por tu interés en contribuir! 🎉

## Configuración del entorno

```bash
# Clonar el repositorio
git clone https://github.com/xmon-org/ai-content-bundle.git
cd ai-content-bundle

# Instalar dependencias
composer install

# Verificar que todo funciona
composer check
```

## Comandos útiles

```bash
# Ejecutar tests
composer test

# Análisis estático con PHPStan
composer phpstan

# Verificar estilo de código
composer cs-check

# Corregir estilo de código automáticamente
composer cs-fix

# Ejecutar todas las verificaciones
composer check

# Generar baseline de PHPStan (si hay errores legacy)
composer phpstan:baseline
```

## Git Hooks

Git hooks are configured automatically on `composer install`.

### Pre-commit Hook

Runs automatically before each commit:

1. **PHP-CS-Fixer**: Auto-formats staged PHP files
2. **PHPStan**: Static analysis on modified files in `src/` and `tests/`

If PHPStan finds errors, the commit is rejected.

### Bypassing Hooks (Not Recommended)

```bash
git commit --no-verify -m "message"
```

Only use in emergencies. Hooks prevent quality issues.

### Manual Hook Setup

If hooks are not working:

```bash
composer setup-hooks
```

## Convención de commits

Este proyecto usa [Conventional Commits](https://www.conventionalcommits.org/) para automatizar el versionado y el changelog.

### Formato

```
<tipo>(<scope>): <descripción>

[cuerpo opcional]

[footer opcional]
```

### Tipos permitidos

| Tipo       | Descripción                              | Release   |
|------------|------------------------------------------|-----------|
| `feat`     | Nueva funcionalidad                      | MINOR     |
| `fix`      | Corrección de bug                        | PATCH     |
| `docs`     | Solo documentación                       | -         |
| `style`    | Formato (espacios, comas, etc)           | -         |
| `refactor` | Refactoring sin cambio de funcionalidad  | PATCH     |
| `perf`     | Mejora de rendimiento                    | PATCH     |
| `test`     | Añadir o corregir tests                  | -         |
| `ci`       | Cambios en CI/CD                         | -         |
| `chore`    | Mantenimiento                            | -         |

### Scopes sugeridos

- `text` - Generación de texto
- `image` - Generación de imágenes
- `provider` - Proveedores de IA
- `config` - Configuración del bundle
- `sonata` - Integración con Sonata

### Ejemplos

```bash
# Nueva funcionalidad
git commit -m "feat(text): add Claude provider support"

# Bug fix
git commit -m "fix(image): resolve timeout with large prompts"

# Breaking change
git commit -m "feat(provider)!: change provider interface

BREAKING CHANGE: ProviderInterface now requires getModel() method"

# Documentación
git commit -m "docs: add image generation examples"

# Refactoring
git commit -m "refactor(text): simplify fallback logic"
```

## Proceso de Pull Request

1. **Crea una rama** desde `develop`:
   ```bash
   git checkout develop
   git pull origin develop
   git checkout -b feat/mi-nueva-feature
   ```

2. **Haz tus cambios** siguiendo las convenciones

3. **Asegúrate de que pasan las verificaciones**:
   ```bash
   composer check
   ```

4. **Haz commit** con conventional commits

5. **Push** y crea un PR hacia `develop`:
   ```bash
   git push -u origin feat/mi-nueva-feature
   ```

## Estructura del proyecto

```
src/
├── DependencyInjection/    # Configuración del bundle
├── Provider/               # Proveedores de IA
│   ├── Text/               # Proveedores de texto
│   └── Image/              # Proveedores de imagen
├── Service/                # Servicios principales
└── XmonAiContentBundle.php # Bundle principal

tests/
└── ...                     # Tests unitarios y funcionales

docs/
└── ...                     # Documentación
```

## ¿Preguntas?

Abre un issue si tienes dudas o sugerencias.
