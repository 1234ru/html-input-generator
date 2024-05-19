<?php


namespace One234ru;

use One234ru\HTMLtagGenerator;

class HTMLinputGenerator extends HTMLtagGenerator
{
    private $currentFieldValue;

    /**
     * @param $cfg = [
     *  'type' => string,
     *  'name' => string,
     *  'attr' => array,
     *  'multiple' => true, // select
     *  'options' => [ // select
     *      'value' => 'text',
     *      [
     *          'value' => string,
     *          'text' => string,
     *          'attr' => []
     *      ]
     *  ],
     *  'value' => '',
     * ]
     */
    public function __construct($cfg, $current_field_value = null)
    {
        $this->currentFieldValue = $current_field_value;
        parent::__construct($cfg);
    }

    protected function normalizeConfig($cfg) {
        $value = $this->currentFieldValue;
        switch ($type = $cfg['type'] ?? 'text') {
            case 'file':
                $config = self::configForInputOfType($type, $cfg['name'] ?? null);
                break;
            case 'hidden': case 'submit': case 'reset':
                $config = self::configForInputOfType($type, $cfg['name'] ?? null);
                if (isset($cfg['value'])) {
                    $config['attr']['value'] = $cfg['value'];
                } elseif ($cfg['type'] == 'hidden') {
                    $config['attr']['value'] = $this->currentFieldValue;
                }
                break;
            case 'checkbox': case 'radio':
                $config = self::configForCheckboxAndRadio($cfg, $value);
                break;
            case 'textarea':
                $config = [
                    'tag' => 'textarea',
                ];
                if (isset($cfg['name'])) {
                    $config['text'] = htmlspecialchars((string) $value);
                    $config['attr']['name'] = $cfg['name'];
                }
                break;
            case 'select':
                try {
                    $config = self::makeSelectConfig($cfg, $value);
                } catch (\Exception $e) {
                    $msg = $e->getMessage() . "\n"
                        . "The given config is:\n"
                        . print_r($e->getTrace()[0]['args'][0], 1);
                    trigger_error($msg, E_USER_WARNING);
                    return null;
                }
                break;
            default: // <input type="text"> and others alike
                $config = self::configForInputOfType($type, $cfg['name'] ?? null);
                if (isset($cfg['name'])) {
                    $config['attr']['value'] = $value;
                }
                break;
        }

        if (!isset($config['attr'])) {
            $config['attr'] = [];
        }
        $config['attr'] += $cfg['attr'] ?? [];

        return $config;
    }

    private static function configForInputOfType(
        string $type,
        string $name = null
    ) :array {
        $config = [
            'tag' => 'input',
            'attr' => [
                'type' => $type,
            ]
        ];
        if (!is_null($name)) {
            $config['attr']['name'] = $name;
        }
        return $config;
    }

    private static function configForCheckboxAndRadio($cfg, $value_to_match) {
        $multiple = ($cfg['multiple'] ?? false);
        if (isset($cfg['name'])) {
            $name = $cfg['name'];
            if ($multiple) {
                $name .= '[]';
            }
        }
        else {
            $name = null;
        }

        $config = self::configForInputOfType($cfg['type'], $name);

        if (isset($cfg['value'])) {
            $config['attr']['value'] = $cfg['value'];
        }
        self::establishSettableItemConfig(
            $config,
            'checked',
            $value_to_match,
            $multiple
        );

        return $config;
    }

    /**
     * Non-strict comparison of values, considering some special cases;
     * In multiple mode first value is a "needle", second value is a "haystack" array.
     */
    private static function doValuesMatch(
        string $field_value,
        $value_to_match,
        $multiple = false
    ) :bool {
        // Comparison is not strict, becase values may come both
        // from HTTP query, where every literal is a string,
        // and from any arbitrary data where literals may be numbers
        // or something else.
        // For example, you certainly don't want to get an unchecked checkbox
        // with value '1' while checking against an interger 1.
        // But to make things less ambigous the 'value' attribute
        // has to be converted to string before comparison,
        // so $field_value parameter is decalred as string.
        if (!$multiple) {
            if ($field_value === '' and $value_to_match === 0) {
                // We need to disambiguate '' == 0 and '0' == 0
                // both returning true, converting first case to false.
                $match = false;
            } else {
                $match = ($field_value == $value_to_match);
            }
        } else {
            $match = false;
            if (is_array($value_to_match)) {
                $fn = __FUNCTION__;
                // Manually iterating over an array instead of calling
                // in_array() to accurately replicate behaviour
                // of $multiple = false.
                foreach ($value_to_match as $val) {
                    if (self::$fn($field_value, $val)) {
                        $match = true;
                        break;
                    }
                }
            }
        }
        return $match;
    }

    /**
     * @throws \Exception
     */
    private static function makeSelectConfig($cfg, $value_to_check_options_against) {
        $config = [
            'tag' => 'select'
        ];

        $multiple = $cfg['multiple'] ?? false;
        if (isset($cfg['attr']['multiple'])) {
            $msg = "'multiple' setting should be set at the top level"
                . " (near 'type'), not in the 'attr' array.";
            throw new \Exception($msg);
        }
        if ($multiple) {
            $config['attr']['multiple'] = true;
        }

        if (isset($cfg['name'])) {
            $config['attr']['name'] = $cfg['name'];
            if ($multiple) {
                $config['attr']['name'] .= '[]';
            }
        }

        $config['children'] = [];
        if (isset($cfg['options'])) {
            $config['children'] = array_merge(
                $config['children'],
                self::makeChildrenConfigFromSelectOptions(
                    $cfg['options'],
                    $value_to_check_options_against,
                    $multiple
                )
            );
        }
        if (isset($cfg['optgroups'])) {
            $config['children'] = array_merge(
                $config['children'],
                self::makeChildrenConfigFromSelectOptgroups(
                    $cfg['optgroups'],
                    $value_to_check_options_against,
                    $multiple
                )
            );
        }

        return $config;
    }

    private static function makeChildrenConfigFromSelectOptgroups(
        $optgroups,
        $value_to_check_options_against,
        $multiple
    ) {
        foreach ($optgroups as $optgroup) {
            $children[] = [
                'tag' => 'optgroup',
                'attr' => $optgroup['attr'] ?? [],
                'children' => self::makeChildrenConfigFromSelectOptions(
                    $optgroup['options'] ?? [],
                    $value_to_check_options_against,
                    $multiple
                )
            ];
        }
        return $children ?? [];
    }

    private static function makeChildrenConfigFromSelectOptions(
        $options,
        $value_to_check_options_against,
        $multiple
    ) {
        foreach ($options as $key => $option) {
            $children[] = self::makeSelectOptionConfig(
                $key,
                $option,
                $value_to_check_options_against,
                $multiple
            );
        }
        return $children ?? [];
    }

    private static function makeSelectOptionConfig(
        $key,
        $option,
        $value_to_match,
        $multiple
    ) {
        $config = [
            'tag' => 'option'
        ];

        if (!is_array($option)) {
            $config['text'] = $option;
            $config['attr']['value'] = $key;
        } else {
            if (isset($option['value'])) {
                $config['attr']['value'] = $option['value'];
            }
            $config['text'] = $option['text'] ?? '';
        }

        self::establishSettableItemConfig(
            $config,
            'selected',
            $value_to_match,
            $multiple
        );

        if (!isset($config['attr'])) {
            $config['attr'] = [];
        }
        $config['attr'] += $option['attr'] ?? [];

        return $config;
    }

    /**
     * Converts value to string and sets checked/selected attribute.
     * @param array $config = [
     *     'attr' => [ 'value' => string ]
     * ]
     * @param $attr_name_to_set = 'checked|selected'
     */
    private static function establishSettableItemConfig(
        &$config,
        string $attr_name_to_set,
        $value_to_match,
        bool $multiple
    ) {
        if (isset($config['attr']['value'])) {
            $value_actual = &$config['attr']['value'];
            // Value is converted to string beforehand.
            // See comment in doValuesMatch() method for explanation.
            $value_actual = strval($value_actual);
            if (self::doValuesMatch($value_actual, $value_to_match, $multiple)) {
                $config['attr'][$attr_name_to_set] = true;
            }
        }
    }
}