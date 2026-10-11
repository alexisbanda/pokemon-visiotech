<?php

declare(strict_types=1);

namespace Tests\Feature\Api;

use App\Domain\Combat\PokemonType;
use Tests\TestCase;

final class TypeApiTest extends TestCase
{
    public function test_index_lists_the_18_types_in_declaration_order(): void
    {
        $expected = array_map(fn (PokemonType $case): string => $case->value, PokemonType::cases());

        $response = $this->getJson('/api/types')
            ->assertOk()
            ->assertJsonCount(18, 'data');

        $actual = array_map(
            fn (array $item): string => $item['value'],
            $response->json('data'),
        );

        $this->assertSame($expected, $actual);
    }
}
