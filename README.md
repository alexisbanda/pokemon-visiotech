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
sail artisan battle:simulate 1 2                 # Charizard vs Blastoise (auto)
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

## API

Base: `http://localhost/api`. Todas las respuestas usan **API Resources**; los
errores siguen la semántica HTTP (201/204/404/422/409). Colección lista para
probar en `docs/api.http` (extensión REST Client de VSCode).

### Pokédex (Parte 2)

| Método | Ruta | Descripción |
|---|---|---|
| `GET/POST` | `/pokemon` | Listar / crear Pokémon base |
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

## `battle:simulate`

Juega un combate completo entre dos `MyPokemon` y narra cada turno con el
marcador (ambas barras de PS) y avisos de efectividad. Reutiliza el mismo
`BattleService` que la API. Los PS se inicializan **escalados al nivel** para que
el combate dure varios turnos.

```bash
sail artisan battle:simulate <idA> <idB> [opciones]
sail artisan battle:simulate                 # sin ids: eliges los combatientes en un menú
```

| Opción | Efecto |
|---|---|
| `--seed=N` | Combate **reproducible** (mismo resultado siempre) |
| `--interactive` | **Tú vs CPU**: controlas el primer combatiente, la CPU el otro |
| `--no-delay` | Sin pausa entre turnos (CI/tests) |
| `--ascii` | Salida sin emojis |

> La duración depende del enfrentamiento: dos Pokémon resistentes y sin ventaja
> de tipo dan combates largos; un golpe supereficaz contra un Pokémon frágil
> puede acabar en pocos turnos (igual que en el juego real).

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
