**[IN ENGLISH](README.md)**


# Генерация HTML-кода полей форм на основе конфигурационных массивов

Библиотека позволяет генерировать исходный HTML-код
различных полей форм — 
`<input>`, `<select>` и `<textarea>` — 
на основании простых конфигураций.

Также возможна подстановка в поля значений,
чтобы они сгенерировались уже заполненными.

Данная библиотека является надстройкой над [one234ru/html-tag-generator](https://github.com/1234ru/html-tag-generator).


## Установка

```shell
composer require one234ru/html-input-generator 
```


## Использование

Чтобы получить HTML-код поля формы, нужно создать объект поля,
передав конструктору конфигурацию и значение для подстановки в поле.
Объект при преобразовании в строку дает HTML поля.

В примере ниже генерируется `<input type="text" name="something">`
с подстановкой значения из массива `$_GET`:

```php
$config = [
    'type' => 'text',
    'name' => 'something',
];
$value = $_GET['something'] ?? '';
$input = new \One234ru\HTMLinputGenerator($config, $value);
$html = strval($input);
```

Практически в любой конфигурации есть параметры `type`, `name` и `attr`:

* `name` подставляется в одноименнный атрибут  
  (если не указан, атрибута у поля не будет)  
  

* `attr` содержит перечень остальных атрибутов в формате ключ-значение
  (в т.ч. `class`, `placeholder` и `style`)  
  

* `type`, он определяет, поле какого типа будет сгенерировано;   
  от него также зависит набор остальных параметров

`type` может содержать следующие значения.


### `'text'` или пустая строка (значение по умолчанию)

Генерируется `<input type="text">`.
В атрибут `value` подставляется переданное конструктору значение.
Перед вставкой оно обрататывается с помощью `htmlspecialchars()`:

```php
$config = [
    'type' => 'text',
    'name' => 'something',
    'attr' => [
        'class' => 'some-class',
        'placeholder' => 'Введите "что-нибудь" сюда'
    ]
];
$value = 'Текст с "кавычками" + <script>хакеры!</script>';
```

Результат (отформатирован для удобочитаемости):

```html
<input 
 type="text"
 name="something"
 value="Текст с &amp;quot;кавычками&amp;quot; + &amp;lt;script&amp;gt;хакеры!&amp;lt;/script&amp;gt;"
 class="some-class"
 placeholder="Введите &amp;quot;что-нибудь&amp;quot; сюда"
>
```

`'text'` является значением по умолчанию и будет применён,
если `type` пуст или вовсе отсутствует. 


### `'textarea'`

Будет сгенерирован тег `<textarea>`,
а переданное значение подставлено в качестве содержимого:

```php
$config = [
    'type' => 'textarea',
    'name' => 'something',
    'attr' => [
        'rows' => 5,
        'style' => 'width: 100%; line-height: 1.25;'
    ]
];
$value = '"кавычки" и <script>';
```

Результат (отформатирован для удобочитаемости):

```html
<textarea 
 name="something"
 rows="5"
 style="width: 100%; line-height: 1.25;"
>&amp;quot;кавычки&amp;quot; и &amp;lt;script&amp;gt;</textarea>
```


### `'checkbox'` и `'radio'`

Такой вариант даст `<input>` соответствующего типа.

В конфигурации также можно указать параметр `value`. 
Если он совпадёт с переданным конструктору значением,
у поля будет установлен атрибут `checked`:

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

При сопоставлении проводится нестрогое сравнения. При этом конкретно для случая 
сопоставления числового `0` и пустой строки возвращается `false`.


### `'submit'`, `'reset'`

`<input>` соответствующего типа с возможным указанием `value`
для подстановки в одноименный атрибут.

Второй аргумент конструктора никакого влияния
на конечный HTML не оказывает.

```php
$config = [
    'name' => 'something',
    'type' => 'checkbox',
    'value' => '10'
];
$value = 'что угодно';
```

```html
<input type="hidden" name="secret" value="10">
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

Если в конфигурации значение `value` указано явно, второй аргумент будет проигнорирован:

```php
$config = [
    'name' => 'something',
    'type' => 'hidden',
    'value' => 'steady'
];
$value = 'custom'; // будет проигнорировано
```
```html
<input type="hidden" name="secret" value="steady">
```

### `'file'`

Даст `<input type="file">`. Из остальных параметров
учитываются только `name` и `attr`.


### `'select'`

Генерируется тег `<select>`.

К стандартным параметрам конфигурации добавляются `options`, `optgroups` и `multiple`.

#### Параметр `options`

Этот параметр содержит список вариантов выбора.
Каждый из них может быть представлен двумя способами:

1. В виде массива с ключами `value`, `text` и, опционально, `attr`.
`value` становится одноименным атрибутом, а `text` — содержимым тега `<option>`. 
   
2. В виде пары ключ-значение. 
В этом случае ключ используется как `value`, а значение — как `text`. 

Вариантам, чьё `value` совпадает с переданной конструктору величиной, 
устанавливается атрибут `selected`:

```php
$config = [
    'type' => 'select',
    'name' => 'something',
    'options' => [
        '' => '(выбрать)',
        1 => 'Раз',
        2 => 'Два',
        [
            'value' => 3,
            'text' => 'Три',
            'attr' => [
                'data-something' => 'Что-то',
            ]
        ]
    ]
];
$value = '3';
```

HTML (отформатирован для удобочитаемости):

```html
<select name="something">
  <option value="">(выбрать)</option>
  <option value="1">Раз</option>
  <option value="2">Два</option>
  <option value="3" data-something="Что-то" selected>Три</option>
</select>
```

#### Флаг `multiple`

Включение этого параметра, помимо добавления HTML-тегу одноименного атрибута,
влияет на два важных аспекта работы механизма:

1. Атрибуту `name` прибавляется пустая пара квадратных скобок — `[]`.  
   (Это необходимо для передачи в HTTP-запрос нескольких значений из одного поля.)  
   Поэтому ***не следует дописывать `[]` самостоятельно***.


2. Меняется характер сопоставления значений вариантов переданной конструктору величине: 
вместо простого сравнения происходит поиск в массиве.

```php
$config = [
    'type' => 'select',
    'name' => 'something',
    'multiple' => true,
    'options' => [
        '' => '(выбрать)',
        1 => 'Раз',
        2 => 'Два',
        3 => 'Три',
    ]
];
$value = [ '1', '3' ];
```
```html
<select multiple name="something[]">
  <option value="">(выбрать)</option>
  <option value="1" selected>Раз</option>
  <option value="2">Два</option>
  <option value="3" selected>Три</option>
</select>
```

#### Параметр `optgroups`

С помощью этого параметра варианты выбора могут быть сгруппированы в теги `<optgroup>`. Эти 
теги также могут быть снабжены атрибутами, среди которых нужно отметить `label` — 
отображаемый заголовок группы.

Группы могут соседствовать с отдельно стоящими вариантами.

```php
$config = [
    'type' => 'select',
    'name' => 'something',
    'options' => [
        '' => '(выбрать)',
    ],
    'optgroups' => [
        [
            'attr' => [
                'label' => 'Первая группа',
            ],
            'options' => [
                1 => 'Раз',
                2 => 'Два',
            ]
        ],
        [
            'attr' => [
                'label' => 'Вторая группа',
            ],
            'options' => [
                3 => 'Три',
                4 => 'Четыре',
            ]
        ]
    ]
];
$value = '3';
```
```html
<select name="something">
   <option value="">(выбрать)</option>
   <optgroup label="Первая группа">
      <option value="1">Раз</option>
      <option value="2">Два</option>
   </optgroup>
   <optgroup label="Вторая группа">
      <option value="3" selected>Три</option>
      <option value="4">Четыре</option>
   </optgroup>
</select>
```

### Любое другое значение `type`

Если значение не относится ни к одному из вышеописанных
случаев, то генерируется тег `<input>`, `type` подставляется в его одноименный атрибут,
а переданная конструктору величина — в атрибут `value`.

Результат аналогичен таковому для `type='text'`.

```php
$config = [
    'type' => 'tel',
    'name' => 'something',
    'attr' => [
        'placeholder' => 'Укажите телефон'
    ]
];
$value = '+74950000000';
```
```html
<input
 type="tel"
 name="something" 
 value="+74950000000"
 placeholder="Укажите телефон"
>
```