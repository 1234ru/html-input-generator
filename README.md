**[ПО-РУССКИ](README-RU.md)**


# HTML form input generation based on configuration array

This tool generates HTML source code of miscellaneous web form fields - 
`<input>`, `<select>` and `<textarea>` -
based on simple configurations.


## Installation

This library is based on [one234ru/html-tag-generator](https://github.com/1234ru/html-tag-generator).

Add two new repositories to your `composer.json`:

```json 
"repositories": [
    {
      "type": "git",
      "url": "https://github.com/1234ru/html-tag-generator"
    },
    {
        "type": "git",
        "url": "https://github.com/1234ru/html-input-generator"
    }
]
```

Run `composer require` explicitly specifying `main` branch after `dev-` prefix:

```shell
composer require one234ru/html-input-generator:dev-main 
```


## Usage

To obtain form field's HTML, you need to create field's object,
passing two arguments to it's constructor: 
the field's configuration and a value, corresponding to the field. 
Converting object to string gives the HTML.

In the example below `<input type="text" name="something">` is generated,
it's value is extracted from the `$_GET` array:

```php
$config = [
    'type' => 'text',
    'name' => 'something',
];
$value = $_GET['something'] ?? '';
$input = new \One234ru\HTMLinputGenerator($config, $value);
$html = strval($input);
```

Almost any configuration includes parameters `type`, `name` и `attr`:

* `name` is set as a value of the namesake attribute  
  (if absent, the tag will have no attribute)
  

* `attr` is a list of arbitrary attributes in a form of key-value pairs   
  (including `class`, `placeholder` и `style`)  
  

* `type` — defines which type of field will be generated;  
  also affects processing of additional parameters 

`type` may hold following values. 


### `'text'` or empty string

An `<input type="text">` is generated.
The `value` attribute is set to the second constructor's argument, processed with 
`htmlspecialchars()`:

```php
$config = [
    'type' => 'text',
    'name' => 'something',
    'attr' => [
        'class' => 'some-class',
        'placeholder' => 'Type "something" here'
    ]
];
$value = 'Text with "quotes" + <script>hackers!</script>';
```

Result (formatted for readability):

```html
<input 
 type="text"
 name="something"
 value="Text with &amp;quot;quotes&amp;quot; + &amp;lt;script&amp;gt;hackers!&amp;lt;/script&amp;gt;"
 class="some-class"
 placeholder="Type &amp;quot;something&amp;quot; here"
>
```

`'text'` is a default value for `type` and will be applied
if it is empty or absent.



### `'textarea'`

A `<textarea>` will be generated, with the value is used as contents:

```php
$config = [
    'type' => 'textarea',
    'name' => 'something',
    'attr' => [
        'rows' => 5,
        'style' => 'width: 100%; line-height: 1.25;'
    ]
];
$value = '"quoted" and <script>';
```

Result (formatted for readability):

```html
<textarea 
 name="something"
 rows="5"
 style="width: 100%; line-height: 1.25;"
>&amp;quot;quoted&amp;quot; and &amp;lt;script&amp;gt;</textarea>
```


### `'checkbox'` and `'radio'`

This variant yields an `<input>` of the corresponding type.

The `value` parameter may be specified. If it matches
the value passed to the constructor,
field's `checked` attribute is turned on:

```php
$cfg = [
    'type' => 'checkbox',
    'name' => 'something',
    'value' => 1,
];
$value = '1';
```

```html
<input type="checkbox" name="something" value="1" checked>
```

Non-strict comparison is performed when matching. `false` is returned in the particular case of matching integer `0` and an empty string. 


### `'submit'`, `'reset'`

An `<input>` of the corresponding type is generated.

The `value` parameter, if specified, goes to the namesake attribute.

Constructor's second agrument has no effect.

```php
$config = [
    'name' => 'secret',
    'type' => 'hidden',
    'value' => 'nobody should see this'
];
$value = 'whatever';
```

```html
<input type="hidden" name="secret" value="nobody should see this">
```


### `'hidden'`

```php
$config = [
    'name' => 'something',
    'type' => 'hidden',
];
$value = 'custom';
```
```html
<input type="hidden" name="secret" value="custom">
```

If `value` is explicit, second agrument will be ignored:

```php
$config = [
    'name' => 'something',
    'type' => 'hidden',
    'value' => 'steady'
];
$value = 'custom'; // ignored
```
```html
<input type="hidden" name="secret" value="steady">
```


### `'file'`

Yields `<input type="file">`. Other working parameters are `name` and `attr`.


### `'select'`

A `<select>` tag is generated.

`options`, `optgroups` and `multiple` join standard configuration parameters.

#### `options` parameter

Holds list of options, any of which may be declared in two ways:

1. As an array with keys `value`, `text` and, optionally, `attr`.  
`value` becomes namesake's attribute value, `text` — `<option>` tag's contents.  
   
   
2. As a key-value pair.  
In this case a key is treated as `value`, and a value — as `text`.

If an option's `value` matches the value passed to constructor, 
it's `selected` attribute is set:

```php
$config = [
    'type' => 'select',
    'name' => 'something',
    'options' => [
        '' => '(choose)',
        1 => 'One',
        2 => 'Two',
        [
            'value' => 3,
            'text' => 'Three',
            'attr' => [
                'data-something' => 'Something',
            ]
        ]
    ]
];
$value = '3';
```

HTML (formatted for readability):

```html
<select name="something">
  <option value="">(choose)</option>
  <option value="1">One</option>
  <option value="2">Two</option>
  <option value="3" data-something="Something" selected>Three</option>
</select>
```

#### `multiple` flag

Turning this parameter on sets the namesake attribute and also
affects two major aspects:

1. The `name` attribute is appended with a pair of empty brackets — `[]`.  
   So ***don't put `[]` there yourself***.
   

2. The way of matching options' values to the value, passed to constructor, is changed:
search in array is done instead of simple comparison.

```php
$config = [
    'type' => 'select',
    'name' => 'something',
    'multiple' => true,
    'options' => [
        '' => '(choose)',
        1 => 'One',
        2 => 'Two',
        3 => 'Three',
    ]
];
$value = [ '1', '3' ];
```
```html
<select multiple name="something[]">
  <option value="">(choose)</option>
  <option value="1" selected>One</option>
  <option value="2">Two</option>
  <option value="3" selected>Three</option>
</select>
```

#### `optgroups` parameter

This parameter groups options into `<optgroup>` tags. This tags may also have attributes, 
particularly `label` — visible group title. 

Groups and standalone options may coexist.

```php
$config = [
    'type' => 'select',
    'name' => 'something',
    'options' => [
        '' => '(choose)',
    ],
    'optgroups' => [
        [
            'attr' => [
                'label' => 'First group',
            ],
            'options' => [
                1 => 'One',
                2 => 'Two',
            ]
        ],
        [
            'attr' => [
                'label' => 'Second group',
            ],
            'options' => [
                3 => 'Three',
                4 => 'Four',
            ]
        ]
    ]
];
$value = '3';
```
```html
<select name="something">
   <option value="">(choose)</option>
   <optgroup label="First group">
      <option value="1">One</option>
      <option value="2">Two</option>
   </optgroup>
   <optgroup label="Second group">
      <option value="3" selected>Three</option>
      <option value="4">Four</option>
   </optgroup>
</select>
```

### Any other `type` value

If a value doesn't fall under any of the cases above, an `<input>` tag is generated,
`type` goes straight to tag's namesake attribute,
while the value passed to constructor — to tag's `value` attribute.

Result is very similar to `type='text'` case. 

```php
$config = [
    'type' => 'tel',
    'name' => 'something',
    'attr' => [
        'placeholder' => 'Enter your phone'
    ]
];
$value = '+74950000000';
```
```html
<input
 type="tel"
 name="something" 
 value="+74950000000"
 placeholder="Enter your phone"
>
```