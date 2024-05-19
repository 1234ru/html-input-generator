<?php

require_once __DIR__ . '/vendor/autoload.php';

$query = [
    'something' => $_GET['something'] ?? 'Some "quoted" text + <script>hacker!</script>',
];

// Везде указываем 'name' => 'something' явно,
// чтобы копировать в README без изменений.

$type = $_GET['type'] ?? 'text';

switch ($type) {
    case 'text':
        $config = [
            'type' => 'text',
            'name' => 'something',
            'attr' => [
                'class' => 'some-class',
                'placeholder' => 'Enter "something" here'
            ]
        ];
        break;
    case 'textarea':
        $config = [
            'type' => 'textarea',
            'name' => 'something',
            'attr' => [
                'rows' => 5,
                'style' => 'width: 100%; line-height: 1.25;'
            ]
        ];
        break;
    case 'checkbox': case 'radio':
        $config = [
            'type' => 'checkbox',
            'name' => 'something',
            'value' => 1,
        ];
        break;
    case 'hidden'; // case 'submit'; case 'reset';
        $config = [
            'name' => 'secret',
            'type' => 'hidden',
            'value' => 'no one should see this'
        ];
        break;
    case 'file':
        $config = [
            'name' => 'somefile',
            'type' => 'file',
            'attr' => [
                'class' => 'file-input',
            ]
        ];
    case 'select':
        $config = [
            'type' => 'select',
            'name' => 'something',
            'multiple' => true,
            // 'attr' => [
            //     'size' => 2
            // ],
            'options' => [
                '' => '(выбрать)',
                1 => 'Раз',
            ],
            'optgroups' => [
                [
                    'attr' => [
                        'label' => 'Группа вариантов',
                    ],
                    'options' => [
                        2 => 'Два',
                        [
                            'value' => 3,
                            'text' => 'Три',
                            'attr' => [
                                'data-something' => 'Что угодно',
                            ]
                        ]
                    ]
                ]
            ]
        ];
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
        $query[$config['name']] = ($config['multiple'] ?? false)
            ? [ 1, 3 ]
            : 3 ;
        break;
    default:
        $config = [
            'type' => $_GET['type'],
            'name' => 'something',
        ];
        break;
}

$value = $query[$config['name']] ?? null;

$html = new One234ru\HTMLinputGenerator($config, $value);

echo "<form style='padding-bottom: 1em; margin-bottom: 1em; border-bottom: 1px solid;'>"
    . '<input type="hidden" name="type" value="' . $type . '">'
    . "<p style='font-weight: bold'>HTML:</p>"
    . "<div style='padding: 1em; background: #F8f8f8'>\n$html\n</div>"
    . '<button>Send</button><input type="reset" value="Reset">'
    . "<p style='font-weight: bold'>HTML source:</p>"
    . "<pre style='padding: 1em; background: #F8f8f8'>\n" . htmlspecialchars($html) ."\n</pre>"
    . "<p>Config:</p>"
    . "<pre style='padding: 1em; background: #F8f8f8'>"
    . htmlspecialchars(var_export($config, true))
    . "</pre>"
    . "</form>";