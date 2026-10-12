<?php

declare(strict_types=1);

namespace Tests\Feature\Api;

use Tests\TestCase;

final class TypeApiTest extends TestCase
{
    public function test_index_lists_the_18_types_in_declaration_order(): void
    {
        $expected = [
            'normal', 'fire', 'water', 'electric', 'grass', 'ice', 'fighting',
            'poison', 'ground', 'flying', 'psychic', 'bug', 'rock', 'ghost',
            'dragon', 'dark', 'steel', 'fairy',
        ];

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
