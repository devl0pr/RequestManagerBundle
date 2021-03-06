RequestManager Service
============
This service provides an easy validation process of the JSON requests and 
any other user defined processing of request content.

Only following `Content-Type`s supported for now:
`application/json`

#### Table of Contents  

- [Defining a RequestRule](#defining-a-requestrule)  
- [Using the RequestRule](#using-the-requestrule)      
- [Validating without RequestRule](#validating-without-requestrule) 
- [Complex request structures](#complex-request-structures)  
- [Additional methods](#additional-methods)

How it works
============

<a name="defining-a-requestrule"></a>
### Defining a RequestRule

First of all you need to create a class which implements `Devl0pr\RequestManagerBundle\Request\RequestRuleInterface`
or extends `Devl0pr\RequestManagerBundle\Request\AbstractRequestRule`.

It'll require one mandatory and one optional method to implement.

```php
<?php

use Devl0pr\RequestManagerBundle\Request\AbstractRequestRule;
use Devl0pr\RequestManagerBundle\Request\RequestManager;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\Length;

class SomeRequestRule extends AbstractRequestRule
{
    public function getValidationMap()
    {
        // Mandatory method
        return [
            'name' => [
                'constraints' => [
                    new NotBlank(),
                ],
            ],
            'surname' => [
                'constraints' => [
                    new NotBlank(),
                    new Length(['min' => 4]),
                ]
            ]
        ];
    }

    public function onValidationEnd(RequestManager $requestManager)
    {
        // Optional method
        // Do some additional processing here if needed
    }

    public function nameValidation(RequestManager $requestManager)
    {
        // if you define [fieldName]Validation function it will be called automatically\
        // e.g. you can add surnameValidation method either
    }
}
```
#### `getValidationMap()` method:

Array keys here are the corresponding parameter keys in the request body content.

- `constraints` key is a Symfony `Constraint` or a list of `Constraint`s.
    
    The request content will be validated against these values.



#### `onValidationStart()` method:
This method is optional. 
It will be called every time before validation of the request content.

#### `onValidationEnd()` method:
This method is optional.
It will be called every time after validation of the request content.

<a name="using-the-requestrule"></a>
### Using the RequestRule
After you've created a RequestRule let's use it in a controller:

```php
<?php

use Symfony\Component\Routing\Annotation\Route;
use Devl0pr\RequestManagerBundle\Request\RequestManager;
// Assuming that RequestRule was created in the src/RequestRule/ directory
use App\RequestRule\SomeRequestRule;

class SomeController
{
    /**
     * @Route("/", name="app.index")
     */
    public function index(RequestManager $requestManager, SomeRequestRule $requestRule)
    {
        $requestContent = $requestManager->validate($requestRule);

        // ...
    }
}
```

The code above will validate following request content with the given RequestRule
and run all additional methods (fieldNameValidation) you've defined.

```json
{
    "name": "Emil",
    "surname": "Manafov"
}
```

It returns request body content after all manipulations.

If validation fails the `SmartProblemException` will be thrown 
which will be transformed into correct response. 

<a name="validating-without-requestrule"></a>
### Validating without RequestRule
You can validate raw values from the any request bag using `validateManual()` method.

```php
<?php

use Symfony\Component\Routing\Annotation\Route;
use Devl0pr\RequestManagerBundle\Request\RequestManager;
use Symfony\Component\Validator\Constraints\Type;

class SomeController
{
    /**
     * @Route("/", name="app.index")
     */
    public function index(RequestManager $requestManager)
    {
        $page = $requestManager->validateManual('page', [new Type('int')], 1);

        // ...
    }
}
```

If there's a `page` key anywhere in the request bag it will be validated against given constrains, 
otherwise default value of `1` will be returned.

If validation fails the `SmartProblemException` will be thrown 
which will be transformed into correct response. 

<a name="complex-request-structures"></a>
### Complex request structures
You can also validate complex request structures using RequestRules.

Here's an example of RequestRule which will validate the following complex request structure.

Request content:
```json
{
    "list": [1, 2, 3],
    "objects": {
        "name": "Emil",
        "surname": "Manafov"
    },
    "nestedArray": [
        {
            "name": "Emil",
            "surname": "Manafov",
            "ages": [18, 20]
        },
        {
            "name": "Emil",
            "surname": "Manafov",
            "ages": [21, 18, 34]
        }
    ]
}
```

RequestRule:
```php
<?php

use Devl0pr\RequestManagerBundle\Request\AbstractRequestRule;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\Type;
use Symfony\Component\Validator\Constraints\All;
use Symfony\Component\Validator\Constraints\Collection;

class SomeRequestRule extends AbstractRequestRule
{
    public function getValidationMap()
    {
        return [
            'list' => [
                'constraints' => [
                    new NotBlank(),
                    new Type('array'),
                    new All(
                        [
                            'constraints' => [
                                new Type("numeric")
                            ]
                        ]
                    )
                ],
            ],
            'objects' => [
                'constraints' => [
                    new NotBlank(),
                    new Collection(
                        [
                            'name' => [
                                new NotBlank(),
                            ],
                            'surname' => [
                                new NotBlank(),
                                new Length(['min' => 3]),
                            ]
                        ]
                    ),
                ]
            ],
            "nestedArray" => [
                'constraints' => [
                    new NotBlank(),
                    new Type('array'),
                    new All(
                        [
                            'constraints' => [
                                new Collection(
                                    [
                                        'name' => [
                                            new NotBlank(),
                                        ],
                                        'surname' => [
                                            new NotBlank(),
                                            new Length(['min' => 3])
                                        ],
                                        'ages' => [
                                            new NotBlank(),
                                            new Type('array'),
                                            new All(
                                                [
                                                    'constraints' => [
                                                        new Type('int')
                                                    ]
                                                ]
                                            )
                                        ]
                                    ]
                                )
                            ]
                        ]
                    ),
                ]
            ]
        ];
    }
}
```

<a name="additional-methods"></a>
### Additional methods

There are also some helpful methods that can help you in some situations.

Some of them are listed below:

#### Parameter bag

You can use parameter bag to save some values for further processing.

For example, you can add a value to the bag in the RequestRule:

```php
<?php

use Devl0pr\RequestManagerBundle\Request\AbstractRequestRule;
use Devl0pr\RequestManagerBundle\Request\RequestManager;

class SomeRequestRule extends AbstractRequestRule
{
    public function getValidationMap()
    {
        // ...
    }

    public function onValidationEnd(RequestManager $requestManager)
    {
        // ...
        // Add to bag by key
        $requestManager->addToBag('foo', 'bar');
     
    }
}
```

...and retrieve the value in the Controller:

```php
<?php

use Symfony\Component\Routing\Annotation\Route;
use Devl0pr\RequestManagerBundle\Request\RequestManager;

class SomeController
{
    /**
     * @Route("/", name="app.index")
     */
    public function index(RequestManager $requestManager)
    {
        // ...
        // Retrieve a value by key
        $foo = $requestManager->getFromBag('foo'); //bar
    }
}
```

That's it.