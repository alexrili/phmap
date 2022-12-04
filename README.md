# Phmap
This is just a payload map written in php.
This lib helps you to get values from A structure and put it on B struscture

![Screenshot 2022-12-04 at 02 09 39](https://user-images.githubusercontent.com/1238430/205475678-9fde3bca-e07f-4e4d-a388-a846f586816b.png)


**Note:** Phmap uses `illuminate/support` under the hood. If you are using laravel or lumen framework, make sure that you version is  **>= 9.x**, if you're not using any of these frameworks or this dependency(illuminate/support) , than you have nothing to worry about.

## Install

Via Composer

``` bash
$ composer require alexrili/phmap
```



## Basic usage
Under the hood we provide a `payload_map()` helper function. 
The `payload_map()` accpets two arguments: 1º is your input data, 2º is your map config. 
Both needs to be array type. 
Let`s see the example bellow.

``` php
#can be a response from api request
$inputData = [
    'a' => 'Value from A'
];

$map = [
    'from' => 'a',
    'to' => 'b'
];

$response = payload_map($inputData, $map);
```
```
// var_dump($response);
// return will be
array:1 [
    "b" => "Value from A"
]
```

Now let's imagine that you have a multliple values from an array and needs to 
change the inside properties. With **Phmap** you can do that :)

``` php
# e.g: Imagine that you have the following payload

$oldPayload = [
    'addresses' => [
        [
            'mainAddress' => true,
            'address' => '54 St',
            'code' => '676329-098'
        ],
        [
            'mainAddress' => false,
            'address' => 'Saint Louis Av',
            'code' => '4432-098'
        ]
    ]
];

$map = [
    [
        'from' => 'addresses.*.address',
        'to' => 'users_addresses.*.street_name'
    ],
    [
        'from' => 'addresses.*.code',
        'to' => 'users_addresses.*.postal_code'
    ]    
];

$newPayload = payload_map($inputData, $map);
```
```
// var_dump($newPayload);
// return will be
array:2 [
     "users_addresses" => [
        [
            "street_name" => "54 St",
            "postal_code" => "676329-098"
        ],
        [
            "street_name" => "Saint Louis Av",
            "postal_code" => "4432-098
        ]
    ]
]
```
> Notice: The `mainAddress` property was not in the new payload, this is happening
 because we're not tells the payload map to map that

## Map configs

### Direct values
> you can map a value from a direct path in your structure just point where the values are in 
> **from** key and point the destination path within **to** key
```php
[
    "from" =>"a",
    "to" => "b" 
]
```

### Concatanated values 
> you can concatanate one or more values using the `.+.` symbol
```php
[
    "from" =>"a.a1.+.a.a2",
    "to" => "b" 
]
```
### Fixed values
> you can add some fixed values using the`__()__` symbol
```php
[
    "from" =>"__(This is a fixed value)__",
    "to" => "b" 
]
```
### Collection(array) of values
> you can map a nested property inside an array using the  `.*.` symbol
```php
[
    "from" =>"a.*.a1",
    "to" => "b.*.b1" 
]
```

## Advanced usage

```php

$inputData = [
    'a' => [
        [
            'ac1' => 'I`m a multilevel value'
        ],
        [
            'ac1' => 'I`m a multilevel value',
        ]
    ],
    'aDV' => 'I`m a directed value'
];

$map = [
    [
        'from' => 'a.*.ac1.+.__(I`m a fixed value)__.+.aDV',
        'to' => 'b.*.bc'
    ]
];

$response = payload_map($inputData, $map);
```

```
// var_dump($response)
// result wil be
array:1 [
  "b" => array:2 [
    0 => array:1 [
      "bc" => "I`m a multilevel valueI`m a fixed valueI`m a directed value"
    ]
    1 => array:1 [
      "bc" => "I`m a multilevel valueI`m a fixed valueI`m a directed value"
    ]
  ]
]
```


## Change log

Please see [CHANGELOG](CHANGELOG.md) for more information on what has changed recently.

## Testing

``` bash
$ composer test
```

## Contributing

Please see [CONTRIBUTING](CONTRIBUTING.md) and [CODE_OF_CONDUCT](CODE_OF_CONDUCT.md) for details.

## Security

If you discover any security related issues, please email alexrili instead of using the issue tracker.

## Credits

- [Alex Ribeiro][link-author]
- [All Contributors][link-contributors]

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.