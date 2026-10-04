# Pokémon Challenge — Visiotech

Sistema de combate Pokémon en **PHP 8.4 + Laravel 13**, resuelto en 3 partes
incrementales:

1. **Dominio de combate** — cálculo de daño en PHP puro (sin Laravel), con tabla
   de efectividad de los 18 tipos y factor aleatorio inyectable.
2. **API Pokédex** — CRUD de Pokémon, movimientos e instancias (`MyPokemon`, máx.
   4 movimientos), relaciones N:N y 3 consultas relacionales.
3. **API de combate** — máquina de estados de una partida por turnos, más un
   comando de consola `battle:simulate` que juega y narra el combate.

No hay frontend: **la suite de tests es la demostración** y el comando
`battle:simulate` es la demo visual.

---

## Stack

- PHP 8.4 · Laravel 13 (Framework 13.14)
- **Laravel Sail** (Docker) con **MySQL 8.4**
- Tests: PHPUnit · Estilo: Laravel Pint (PSR-12)

## Requisitos

- Docker y Docker Compose
- (Opcional) PHP 8.4 y Composer en local para el primer `composer install`

---

## Arranque

```bash
# 1. Dependencias
composer install

# 2. Entorno
cp .env.example .env

# 3. Levantar contenedores (app en :80, MySQL en :3306)
./vendor/bin/sail up -d

# 4. Clave de app + base de datos con datos de ejemplo
./vendor/bin/sail artisan key:generate
./vendor/bin/sail artisan migrate --seed

# 5. Comprobar que todo está en verde
./vendor/bin/sail test
```

> Alias recomendado: `alias sail='./vendor/bin/sail'`.

La API queda servida en `http://localhost/api`.

---

## Comandos útiles

```bash
sail test                       # suite completa (Unit + Feature)
sail test tests/Unit/Domain     # solo el dominio de combate (Parte 1)
sail artisan migrate:fresh --seed   # recrear BD con datos de ejemplo
./vendor/bin/pint               # formatear a PSR-12

# Simular un combate (Parte 3)
sail artisan battle:simulate 1 2                 # Reptcomputer vs Shellshock (auto)
sail artisan battle:simulate 1 2 --seed=3        # reproducible
sail artisan battle:simulate 1 2 --interactive   # tú vs CPU
```

---

## Arquitectura

```
app/
├─ Domain/Combat/         # Parte 1 — PHP PURO, sin dependencias de Laravel
│  ├─ PokemonType.php      #   enum de los 18 tipos
│  ├─ TypeChart.php        #   tabla de efectividad (lookup, incl. 8 inmunidades)
│  ├─ Stats / Move / Combatant / DamageResult
│  ├─ DamageCalculator.php #   fórmula de daño del enunciado
│  └─ Random/              #   RandomFactor (interfaz) + Standard / Fixed
├─ Models/                # Parte 2/3 — Eloquent (Pokemon, Move, MyPokemon, Battle…)
├─ Http/                  # Parte 2/3 — Controllers finos, Form Requests, API Resources
├─ Services/Combat/       # Parte 3 — BattleService + puente Eloquent↔dominio + renderer
├─ Console/Commands/      # Parte 3 — battle:simulate
└─ Enums/                 # BattleStatus, BattleSide
```

**Principio clave:** `app/Domain/Combat/` no importa `Illuminate\…` jamás. El
motor de daño de la Parte 1 se reutiliza intacto en la API y en el comando de la
Parte 3, traducido desde Eloquent por un `CombatantAssembler`.

### Fórmula de daño (fiel al enunciado)

```
base   = floor( (2 · nivel / 5 + 2) · ataque · poder / defensa / 50 )
daño   = floor( base · efectividad · aleatorio / 100 )
```

El factor `aleatorio` (85–100) se inyecta tras la interfaz `RandomFactor`, de modo
que los tests son deterministas (`FixedRandomFactor`) y la producción es aleatoria
(`StandardRandomFactor`).

---

## Buenas prácticas y decisiones de diseño

El proyecto se construyó con estas decisiones deliberadas (la idea es que cada una
se note en el código, no solo en el papel):

- **Dominio aislado del framework.** Todo `app/Domain/Combat/` es PHP puro: no
  importa `Illuminate\…` jamás. El motor de daño se puede testear y reutilizar sin
  arrancar Laravel, y la API/consola solo lo *traducen* desde Eloquent
  (`CombatantAssembler`). Es el clásico límite "núcleo de negocio ↔ infraestructura".
- **Tipado estricto e inmutabilidad.** `declare(strict_types=1)` en todo el dominio
  y *value objects* inmutables (`Stats`, `Move`, `Combatant`, `DamageResult`): una
  vez creados no mutan, lo que elimina toda una clase de bugs por estado compartido.
- **Inversión de dependencias para la aleatoriedad.** El RNG vive tras la interfaz
  `RandomFactor` y se inyecta por el contenedor. Resultado: producción aleatoria,
  tests 100 % deterministas y un `--seed` que reproduce un combate exacto.
- **Datos sobre condicionales.** La tabla de efectividad de los 18 tipos es un
  *lookup* sobre un `enum` (`PokemonType` → `TypeChart`), no una maraña de
  `if/else` ni strings sueltos; incluye las 8 inmunidades (×0) verificadas contra
  el enunciado.
- **Capas HTTP delgadas.** Controllers finos → la lógica vive en servicios
  (`BattleService`). La **validación** se delega a *Form Requests* (9) y la
  **serialización** a *API Resources* (5): nunca se devuelven modelos Eloquent
  crudos. La semántica HTTP es explícita (201/204/404/422 y **409** al intentar un
  turno en un combate ya terminado).
- **Estado como máquina de estados.** Un `Battle` transita por estados
  (`BattleStatus`) con reglas claras de turno (orden por Velocidad) y de fin
  (PS ≤ 0); las transiciones inválidas fallan de forma controlada.
- **Una sola fuente de verdad para el combate.** La API y el comando
  `battle:simulate` usan **el mismo** `BattleService`: no hay lógica de combate
  duplicada entre web y consola.
- **Tests como demostración.** Sin frontend; la suite (Unit deterministas del
  dominio + Feature de API y combate) es la prueba de que todo funciona.
- **Estilo y consistencia.** Identificadores en inglés (`Fire`, `DamageCalculator`)
  y formato PSR-12 con **Laravel Pint**.
- **Sin sobre-ingeniería.** No hay repositorios sobre Eloquent, ni CQRS, ni event
  sourcing, ni hexagonal completo: solo las capas que el problema realmente pide.

---

## API

Base: `http://localhost/api`. Todas las respuestas usan **API Resources**; los
errores siguen la semántica HTTP (201/204/404/422/409). Colección lista para
probar en `docs/api.http` (extensión REST Client de VSCode).

### Pokédex (Parte 2)

| Método | Ruta | Descripción |
|---|---|---|
| `GET/POST` | `/pokemon` | Listar (filtro opcional `?type=<tipo>`) / crear Pokémon base |
| `GET/PUT/PATCH/DELETE` | `/pokemon/{id}` | Ver / actualizar / borrar |
| `GET/POST` | `/moves` | Listar / crear movimientos |
| `GET/PUT/PATCH/DELETE` | `/moves/{id}` | Ver / actualizar / borrar |
| `GET/POST` | `/my-pokemon` | Listar / crear instancias (máx. 4 movimientos) |
| `GET/PUT/PATCH/DELETE` | `/my-pokemon/{id}` | Ver / actualizar / borrar |

**Las 3 consultas relacionales del enunciado** (+ una extra):

| Consulta (enunciado) | Ruta |
|---|---|
| 1 — Movimientos de un Pokémon por **tipo** (relación movimientos → tipo → Pokémon) | `GET /pokemon/{id}/moves-by-type` |
| 2 — Movimientos **posibles** (aprendibles) de un Pokémon | `GET /pokemon/{id}/moves` |
| 3 — Pokémon que **comparten** un movimiento | `GET /moves/{id}/pokemon` |
| _(extra)_ Movimientos **equipados** de una instancia | `GET /my-pokemon/{id}/moves` |

### Combate (Parte 3)

| Método | Ruta | Descripción | Códigos |
|---|---|---|---|
| `POST` | `/battles` | Crear combate entre 2 `MyPokemon` | 201 · 422 |
| `GET` | `/battles/{id}` | Estado actual + log de turnos | 200 · 404 |
| `POST` | `/battles/{id}/turns` | Jugar un turno (`move_id`) | 200 · 409 · 422 |

Reglas: ataca primero el de mayor **Velocidad** (empate → el primero, de forma
determinista); cada turno aplica el `DamageCalculator`, resta PS y comprueba la
derrota (PS ≤ 0); pedir turno en un combate terminado devuelve **409**; un
movimiento que no pertenece al atacante devuelve **422**.

---

## Jugar en consola — `battle:simulate`

Es la demo visual del proyecto: juega un combate completo entre dos `MyPokemon`,
narra cada turno con el marcador (ambas barras de PS) y avisa de la efectividad.
Reutiliza el mismo `BattleService` que la API. Los PS se inicializan **escalados al
nivel** para que el combate dure varios turnos.

```bash
sail artisan battle:simulate <idA> <idB> [opciones]
```

### Modo rápido (CPU vs CPU)

Pasa dos ids y mira el combate desarrollarse solo:

```bash
sail artisan battle:simulate 1 2                 # Reptcomputer vs Shellshock
sail artisan battle:simulate 1 2 --seed=3        # exactamente reproducible
```

### Sin ids: eliges en un menú

Si omites uno o ambos argumentos, el comando lista los `MyPokemon` disponibles
(apodo — especie y nivel) y eliges con las flechas:

```bash
sail artisan battle:simulate
```

### Modo interactivo (tú vs CPU)

Con `--interactive` **controlas el primer combatiente**: en cada uno de tus turnos
aparece un menú para elegir el movimiento (con su tipo y poder), y la CPU responde
con el otro Pokémon.

```bash
sail artisan battle:simulate 3 1 --interactive
```

### Opciones

| Opción | Efecto |
|---|---|
| `--seed=N` | Combate **reproducible**: misma semilla → mismo resultado (afecta tanto al daño como a la elección de movimiento de la CPU) |
| `--interactive` | **Tú vs CPU**: eliges los movimientos del primer combatiente a mano |
| `--no-delay` | Sin pausa entre turnos (útil en CI o para leer el log de un tirón) |
| `--ascii` | Salida sin emojis (terminales que no los soporten) |

### Cómo se juega un turno

1. Ataca primero el de mayor **Velocidad** (empate → el primero, determinista).
2. Se aplica el `DamageCalculator` (fórmula del enunciado + factor 85–100), se
   restan PS y se anuncia si fue **supereficaz**, **poco eficaz** o **sin efecto**.
3. El combate termina cuando un Pokémon llega a **PS ≤ 0**; se anuncia el ganador.

> La duración depende del enfrentamiento: dos Pokémon resistentes y sin ventaja
> de tipo dan combates largos; un golpe supereficaz contra un Pokémon frágil
> puede acabar en pocos turnos (igual que en el juego real). Un combate sin
> resolución se corta por seguridad a los 500 turnos.

> **Tip:** si nunca has sembrado la base, ejecuta antes
> `sail artisan migrate --seed` para tener los `MyPokemon` de ejemplo
> (`Reptcomputer`, `Shellshock`, `Sparky`).

---

## Tests

```bash
sail test            # 49 tests · Unit (dominio) + Feature (API y combate)
```

- **Unit** (`tests/Unit/Domain/Combat`): tabla de efectividad y fórmula de daño,
  deterministas con `FixedRandomFactor`.
- **Feature** (`tests/Feature`): CRUD y consultas de la Pokédex, y combate
  (orden por velocidad, fin por PS ≤ 0, transiciones inválidas 409/422).

---

## Datos de ejemplo (seeders)

8 Pokémon reales (Charizard, Blastoise, Venusaur, Pikachu, Gengar, Machamp,
Dragonite, Snorlax), 20 movimientos y 3 instancias listas para combatir
(`Reptcomputer`, `Shellshock`, `Sparky`). Varios movimientos se comparten entre
especies para que la consulta de "Pokémon que comparten un movimiento" devuelva
resultados.
