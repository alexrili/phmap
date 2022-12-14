<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;

class PayloadMapTest extends TestCase
{
    /** @test */
    public function should_return_a_empty_array(): void
    {
        $inputData = [];
        $map = [];
        $response = payload_map($inputData, $map);
        $this->assertEmpty($response);
    }

    /** @test */
    public function should_get_value_from_A_and_put_it_on_B(): void
    {
        $inputData = [
            'a' => 'Value from A'
        ];
        $map = [
            'from' => 'a',
            'to' => 'b'
        ];
        $response = payload_map($inputData, $map);
        $this->assertArrayHasKey('b', $response);
    }

    /** @test */
    public function should_get_all_values_from_A_and_put_it_on_B(): void
    {
        $inputData = [
            ['a' => 'Value from A1'],
            ['a' => 'Value from A2'],
        ];
        $map = [
            [
                'from' => '*.a',
                'to' => '*.b'
            ]
        ];
        $response = payload_map($inputData, $map);
        $this->assertCount(2, $response);
    }


    /** @test */
    public function sould_get_second_value_from_A_and_put_it_on_B(): void
    {
        $inputData = [
            ['a' => 'Value from A1'],
            ['a' => 'Value from A2'],
            ['a' => 'Value from A3'],
        ];
        $map = [
            [
                'from' => '1.a',
                'to' => 'b'
            ]
        ];
        $response = payload_map($inputData, $map);
        $this->assertEquals('Value from A2', $response['b']);
    }

    /** @test */
    public function sould_get_concatenate_values_from_Aa1_plus_Aa2_and_put_it_on_B(): void
    {
        $inputData = [
            'a1' => 'Value from A1',
            'a2' => 'Value from A2',
        ];

        $map = [
            [
                'from' => 'a1.+.a2',
                'to' => 'b'
            ]
        ];
        $response = payload_map($inputData, $map);
        $this->assertEquals('Value from A1Value from A2', $response['b']);
    }

    /** @test */
    public function sould_get_fixed_values_and_put_it_on_B(): void
    {
        $map = [
            [
                'from' => '__(fixedvalue)__',
                'to' => 'b'
            ]
        ];
        $response = payload_map([], $map);
        $this->assertEquals('fixedvalue', $response['b']);
    }

    /** @test */
    public function sould_get_values_from_a_nested_attributes_A_array_and__put_it_on_nested_attributes_B_array(): void
    {
        $inputData = [
            'a' => [
                [
                    'ac1' => 'value from ac1',
                    'ac2' => 'value from ac2',
                ],
                [
                    'ac1' => 'value from ac1',
                    'ac2' => 'value from ac2'
                ]
            ]
        ];

        $map = [
            [
                'from' => 'a.*.ac1.+.a.*.ac2',
                'to' => 'b.*.bc'
            ]
        ];
        $response = payload_map($inputData, $map);

        $this->assertCount(2, $response['b']);
        $this->assertEquals('value from ac1value from ac2', $response['b'][0]['bc']);
        $this->assertEquals('value from ac1value from ac2', $response['b'][1]['bc']);
    }

    /** @test */
    public function sould_get_concatenated_fixed_value_direct_and_multilevel_values_from_a_nested_attributes_A_array_and__put_it_on_nested_attributes_B_array(
    ): void
    {
        $inputData = [
            'a' => [
                [
                    'ac1' => ' I`m a multilevel value'
                ],
                [
                    'ac1' => ' I`m a multilevel value',
                ]
            ],
            'aDV' => ' I`m a directed value'
        ];

        $map = [
            [
                'from' => 'a.*.ac1.+.__( I`m a fixed value )__.+.aDV',
                'to' => 'b.*.bc'
            ]
        ];
        $response = payload_map($inputData, $map);
        $stringfyResponse = json_encode($response);
        $this->assertCount(2, $response['b']);
        $this->assertStringContainsString('I`m a multilevel value', $stringfyResponse);
        $this->assertStringContainsString('I`m a directed value', $stringfyResponse);
        $this->assertStringContainsString('I`m a fixed value', $stringfyResponse);
    }

    /** @test */
    public function b_should_not_contain_nullable_attributes(): void
    {
        $inputData = [
            'a' => [
                'attribute1' => 'existis and has value',
            ]
        ];

        $map = [
            [
                'from' => 'a.attribute1',
                'to' => 'b.attribute1'
            ],
            [
                'from' => 'a.attribute2',
                'to' => 'b.attribute2',
            ],
        ];
        $response = payload_map($inputData, $map);
        $this->assertCount(1, $response['b']);
    }

    /** @test */
    public function b_should_contain_nullable_attributes(): void
    {
        $inputData = [
            'a' => [
                'attribute1' => 'existis and has value',
            ]
        ];

        $map = [
            [
                'from' => 'a.attribute1',
                'to' => 'b.attribute1'
            ],
            [
                'from' => 'a.attribute2',
                'to' => 'b.attribute2',
                'nullable' => 'true',
            ],
            [
                'from' => 'a.attribute3',
                'to' => 'b.attribute3',
                'nullable' => 'false',
            ],
        ];
        $response = payload_map($inputData, $map);
        $this->assertCount(2, $response['b']);
    }

    /** @test */
    public function should_get_first_value_when_first_attributes_has_value(): void
    {
        $inputData = [
            'a' => [
                'attribute1' => 'first value',
                'attribute2' => 'second value',
            ]
        ];

        $map = [
            [
                'from' => 'a.attribute1||a.attribute2',
                'to' => 'b.attribute',
            ]
        ];
        $response = payload_map($inputData, $map);
        $this->assertEquals($inputData['a']['attribute1'], $response['b']['attribute']);
    }

    /** @test */
    public function should_try_to_get_the_value_by_stepping_sequentially_within_the_path(): void
    {
        $inputData = [
            'a' => [
                'attribute1' => null,
                'attribute2' => null,
                'attribute3' => 'value',
            ]
        ];

        $map = [
            [
                'from' => 'a.attribute1||a.attribute2||a.nonexistattribute||a.attribute3',
                'to' => 'b.attribute',
            ]
        ];
        $response = payload_map($inputData, $map);
        $this->assertEquals($inputData['a']['attribute3'], $response['b']['attribute']);
    }

    /** @test */
    public function should_try_to_get_the_fixed_value_when_not_finds_value_on_input_data(): void
    {
        $inputData = [

        ];

        $map = [
            [
                'from' => 'a.noValue||__(fixed value)__',
                'to' => 'b.attribute',
            ]
        ];
        $response = payload_map($inputData, $map);
        $this->assertEquals('fixed value', $response['b']['attribute']);
    }

    /** @test */
    public function should_try_to_get_the_fixed_value_when_not_finds_value_on_multivalue_path(): void
    {
        $inputData = [
            'a' => [
                ['attribute' => null],
                ['attribute' => null],
                ['attribute' => 'value a.1'],
            ],
            'a2' => [
                ['attribute' => 'value a2.1'],
                ['attribute' => null],
                ['attribute' => 'a2test'],
            ],
            'a1' => 'value a1.1'
        ];

        $map = [
            [
                'from' => 'a.*.attribute||a2.*.attribute',
                'to' => 'b.*.newAttribute',
                'nullable' => 'true'
            ]
        ];
        $response = payload_map($inputData, $map);
        $this->assertCount(3, $response['b']);
        $this->assertEquals($inputData['a2'][0]['attribute'], $response['b'][0]['newAttribute']);
        $this->assertEquals($inputData['a2'][1]['attribute'], $response['b'][1]['newAttribute']);
    }

    /** @test */
    public function b_should_be_equal_a(): void
    {
        $a = [
            'attribute' => [
                'nested' => 'value',
            ]
        ];

        $map = [
            [
                'from' => 'attribute',
                'to' => 'attribute',
            ]
        ];
        $b = payload_map($a, $map);
        $this->assertEquals($b, $a);
    }
}