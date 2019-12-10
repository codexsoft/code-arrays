<?php

namespace CodexSoft\Code\Arrays;

/**
 * Consider using https://github.com/bocharsky-bw/Arrayzy
 */
class Arrays
{

    /**
     * @param mixed ...$items
     *
     * @return array
     */
    public static function concat(...$items): array
    {
        $result = [];
        foreach ((array) $items as $item) {
            if (\is_object($item)) {
                $result[] = $item;
            } else {
                $result[] = (array) $item;
            }

        }

        return \array_merge( ...$result );
    }

    /**
     * @param mixed ...$items
     *
     * @return array
     */
    public static function concatUnique(...$items): array
    {
        return \array_unique(self::concat(...$items));
    }

    /**
     * foo[bar][baz] => ['foo', 'bar', 'baz']
     * @param string $string
     *
     * @return array
     */
    public static function fromSquarePath2KeysArray(string $string): array
    {

        $i = 0;
        $result = [];
        $buffer = '';
        while ($i < \strlen($string)) {

            switch ( $string[$i] ) {

                case '[':
                case ']':
                    if ( $buffer !== '' ) {
                        $result[] = $buffer;
                    }
                    $buffer = '';
                    break;

                default:
                    $buffer .= $string[$i];
            }

            $i++;

        }
        return $result;
    }

    /**
     * Преобразует многомерный массив к виду [ 'a.b' => 1, 'a.c' => 2, 'a.c.d' => 3 ]
     *
     * @param array $arrayToImport
     * @param string $currentKeyPrefix
     *
     * @return array
     */
    public static function plainifySquared( array $arrayToImport, $currentKeyPrefix = '' )
    {

        if ( !is_array($arrayToImport) || !count($arrayToImport) ) return [];
        $compiledArray = [];
        foreach( $arrayToImport as $parameter => $value )
        {

            $curVar = $currentKeyPrefix ? $currentKeyPrefix.'['.$parameter.']' : $parameter;

            if ( is_object($value) ) continue;

            if ( !is_array($value) ) {
                $compiledArray[$curVar] = $value;
                //$compiledArray[$currentKeyPrefix.'['.$parameter.']'] = $value;
                //$compiledArray[$currentKeyPrefix.$parameter] = $value;
                continue;
            }

            $compiledArray += self::plainifySquared($value, $curVar);

        }

        return $compiledArray;

    }

    /**
     * Преобразует многомерный массив к виду [ 'a.b' => 1, 'a.c' => 2, 'a.c.d' => 3 ]
     *
     * @param array $arrayToImport
     * @param string $currentKeyPrefix
     * @param string $delimiter
     *
     * @return array
     */
    public static function plainify(array $arrayToImport, $currentKeyPrefix = '', $delimiter = '.'): array
    {

        if ( !is_array($arrayToImport) || !count($arrayToImport) ) return [];
        $compiledArray = [];
        foreach( $arrayToImport as $parameter => $value )
        {

            if ( is_object($value) ) continue;

            if ( !is_array($value) ) {
                $compiledArray[$currentKeyPrefix.$parameter] = $value;
                continue;
            }

            $compiledArray += self::plainify($value, $currentKeyPrefix.$parameter.$delimiter, $delimiter);

        }

        return $compiledArray;

    }

    /**
     * и для упрощения этой конструкции: $array['victim']['car']['customModel']
     * с проверками на существование ключей
     * Arrays::getHierarchy($array,['victim','car','customModel'])
     *
     * consider using https://symfony.com/doc/current/components/property_access.html as alternative
     *
     * @param array $array
     * @param array $path
     *
     * @return mixed|null
     */
    public static function getHierarchy(array $array, array $path)
    {

        if ( !is_array($array) ) return null;
        if ( !count($array) ) return null;
        if ( !is_array($path) ) return null;
        if (!count($path)) return null;

        $current = self::valueOfKey( $array, $path[0], null );
        if ( count($path) == 1 ) return $current;

        $i = 1;

        while ( is_array($current) && $i < count($path) )
        {
            $current = self::valueOfKey( $current, $path[$i], null );
            if ($current === null) return null;
            $i++;
        }

        return $current;

    }

    /**
     * @param array $data
     * @param array $path
     * @param mixed $value
     *
     * @return void
     * @link http://stackoverflow.com/questions/15483496/how-to-dynamically-set-value-in-multidimensional-array-by-reference
     *
     * Usage Arrays::setHierarchy($array, ['hello', 'world'], 42);
     * echo $array['hello']['world'] => 42
     */
    public static function setHierarchy(array &$data, array $path, $value): void
    {
        $temp = &$data;
        foreach ($path as $key) {
            $temp = &$temp[$key];
        }
        $temp = $value;
    }


    /**
     * TODO: getValueOf? safeGet?
     * @param $array
     * @param int|string $key
     * @param mixed $defaultValue
     *
     * @return mixed
     */
    public static function valueOfKey(array $array, $key, $defaultValue = null)
    {
        return \array_key_exists($key, $array) ? $array[$key] : $defaultValue;
    }

    /**
     * Get last value from array
     * @param $array
     *
     * @return mixed
     */
    public function getLast($array)
    {
        return array_values(array_slice($array, -1))[0];
        // array_pop((array_slice($array, -1)));
        // $array[count($array)-1]
    }

    /**
     * Get first value from array
     * @param array $array
     *
     * @return array|mixed|null
     */
    public static function getFirst(array $array)
    {
        if (!\is_array($array)) {
            return $array;
        }

        if (\count($array)) {
            reset($array);
            return current($array);
        }

        return null;
    }

    public function multiFromPlain( $array )
    {

        // TODO: сделать рекурсивно!

        if ( !$array ) return false;

        $result = [];
        foreach ( $array as $key => $value )
        {
            if (!is_string($key)) continue;
            $keyArr = explode( '[', $key );
            if ( count($keyArr) === 1 )
            {
                $result[$keyArr[0]] = $value;
                continue;
            }
            elseif ( count($keyArr) === 2 )
            {
                //$base = $result[$keyArr[0]];
                $base = $keyArr[0];
                if ( !array_key_exists( $base, $result ) )
                    $result[$base] = [];

                $second = rtrim( $keyArr[1], ']' );
                $result[$base][$second] = $value;
                continue;
            }
            elseif ( count($keyArr) === 3 )
            {

                $base = $keyArr[0];

                if ( !array_key_exists($base, $result) )
                    $result[$base] = [];

                //$middle = $keyArr[1];
                //$rest = $keyArr[2];

                //explode('[',$rest);

                $second = rtrim($keyArr[1],']');

                if ( !array_key_exists($second, $result[$base]) )
                    $result[$base][$second] = [];

                $third = rtrim($keyArr[2],']');
                $result[$base][$second][$third] = $value;

                //if ( !array_key_exists($base, $result) )
                //    $result[$base] = [];

                //if ( !array_key_exists($base, $result) )
                //    $result[$base] = [];

                //$second = rtrim($keyArr[1],']');
                //$result[$base][$second] = $value;

                continue;
            }

        }

        return $result;
    }

    public function setKeys( $array, $fieldForKey )
    {
        $readyData = [];
        if ( $array ) foreach ( $array as $element )

            if ( is_array( $element ) )
            {
                if ( array_key_exists( $fieldForKey, $element ) )
                    $readyData[ $element[$fieldForKey] ] = $element;
            }
            elseif ( is_object( $element ) )
            {
                if ( property_exists( get_class($element), $fieldForKey ) )
                    $readyData[ $element->$fieldForKey ] = $element;
            }

        return $readyData;
    }

    /**
     * @param array $array
     * @param $fieldForGrouping
     *
     * @return array
     */
    public static function groupBy(array $array, $fieldForGrouping): array
    {
        $readyData = [];
        foreach ($array as $element) {
            $readyData[ $element[$fieldForGrouping] ][] = $element;
        }

        return $readyData;
    }

    /**
     * @param array $array
     * @param \Closure $closure a function($element): scalar
     *
     * for example:
     * Arrays::groupByClosure($elements, function($element) {
     *     return $element->getName();
     * });
     *
     * @return array
     */
    public static function groupByClosure(array $array, \Closure $closure): array
    {
        $readyData = [];
        foreach ($array as $element) {
            $readyData[$closure($element)][] = $element;
        }

        return $readyData;
    }

    /**
     * @param array $array
     * @param mixed $oldValue
     * @param mixed $newValue
     *
     * @return bool
     */
    public static function replaceValue(array &$array, $oldValue, $newValue): bool
    {
        if (($key = \array_search($oldValue, $array)) === false) {
            return false;
        }

        $array[$key] = $newValue;
        return true;
    }

    /**
     * @param array $array
     * @param mixed $value
     *
     * @return array filtered array
     */
    public static function removeElementsWithValue(array $array, $value): array
    {
        return array_filter($array, function($i) use ($value) {
            return $i !== $value;
        });
    }

    /**
     * Converts an object or an array of objects into an array.
     *
     * @param object|array|string $object the object to be converted into an array
     * @param array $properties a mapping from object class names to the properties that need to
     *     put into the resulting arrays. The properties specified for each class is an array of
     *     the following format:
     *
     * ~~~
     * [
     *     'app\models\Post' => [
     *         'id',
     *         'title',
     *         // the key name in array result => property name
     *         'createTime' => 'created_at',
     *         // the key name in array result => anonymous function
     *         'length' => function ($post) {
     *             return strlen($post->content);
     *         },
     *     ],
     * ]
     * ~~~
     *
     * The result of `ArrayHelper::toArray($post, $properties)` could be like the following:
     *
     * ~~~
     * [
     *     'id' => 123,
     *     'title' => 'test',
     *     'createTime' => '2013-01-01 12:00AM',
     *     'length' => 301,
     * ]
     * ~~~
     *
     * @param boolean $recursive whether to recursively converts properties which are objects into
     *     arrays.
     *
     * @return array the array representation of the object
     * @throws \Exception
     */
    public function toArray($object, $properties = [], $recursive = true)
    {
        if (is_array($object)) {
            if ($recursive) {
                foreach ($object as $key => $value) {
                    if (is_array($value) || is_object($value)) {
                        $object[$key] = $this->toArray($value, $properties, true);
                    }
                }
            }

            return $object;
        } elseif (is_object($object)) {
            if (!empty($properties)) {
                $className = get_class($object);
                if (!empty($properties[$className])) {
                    $result = [];
                    foreach ($properties[$className] as $key => $name) {
                        if (is_int($key)) {
                            $result[$name] = $object->$name;
                        } else {
                            $result[$key] = $this->getValue($object, $name);
                        }
                    }

                    return $recursive ? $this->toArray($result, $properties) : $result;
                }
            }
            //if ($object instanceof Arrayable) {
            //    $result = $object->toArray([], [], $recursive);
            //} else {
            //    $result = [];
            //    foreach ($object as $key => $value) {
            //        $result[$key] = $value;
            //    }
            //}
            $result = [];
            foreach ($object as $key => $value) {
                $result[$key] = $value;
            }

            return $recursive ? $this->toArray($result) : $result;
        } else {
            return [$object];
        }
    }

    /**
     * Merges two or more arrays into one recursively.
     * If each array has an element with the same string key value, the latter
     * will overwrite the former (different from array_merge_recursive).
     * Recursive merging will be conducted if both arrays have an element of array
     * type and are having the same key.
     * For integer-keyed elements, the elements from the latter array will
     * be appended to the former array.
     * @param array $a array to be merged to
     * @param array $b array to be merged from. You can specify additional
     * arrays via third argument, fourth argument etc.
     * @return array the merged array (the original arrays are not changed.)
     */
    public function merge($a, $b)
    {
        $args = func_get_args();
        $res = array_shift($args);
        while (!empty($args)) {
            $next = array_shift($args);
            foreach ($next as $k => $v) {
                if (is_int($k)) {
                    if (isset($res[$k])) {
                        $res[] = $v;
                    } else {
                        $res[$k] = $v;
                    }
                } elseif (is_array($v) && isset($res[$k]) && is_array($res[$k])) {
                    $res[$k] = $this->merge($res[$k], $v);
                } else {
                    $res[$k] = $v;
                }
            }
        }

        return $res;
    }

    /**
     * Retrieves the value of an array element or object property with the given key or property name.
     * If the key does not exist in the array or object, the default value will be returned instead.
     *
     * The key may be specified in a dot format to retrieve the value of a sub-array or the property
     * of an embedded object. In particular, if the key is `x.y.z`, then the returned value would
     * be `$array['x']['y']['z']` or `$array->x->y->z` (if `$array` is an object). If `$array['x']`
     * or `$array->x` is neither an array nor an object, the default value will be returned.
     * Note that if the array already has an element `x.y.z`, then its value will be returned
     * instead of going through the sub-arrays. So it is better to be done specifying an array of key names
     * like `['x', 'y', 'z']`.
     *
     * Below are some usage examples,
     *
     * ~~~
     * // working with array
     * $username = \yii\helpers\ArrayHelper::getValue($_POST, 'username');
     * // working with object
     * $username = \yii\helpers\ArrayHelper::getValue($user, 'username');
     * // working with anonymous function
     * $fullName = \yii\helpers\ArrayHelper::getValue($user, function ($user, $defaultValue) {
     *     return $user->firstName . ' ' . $user->lastName;
     * });
     * // using dot format to retrieve the property of embedded object
     * $street = \yii\helpers\ArrayHelper::getValue($users, 'address.street');
     * // using an array of keys to retrieve the value
     * $value = \yii\helpers\ArrayHelper::getValue($versions, ['1.0', 'date']);
     * ~~~
     *
     * @param array|object $array array or object to extract value from
     * @param string|\Closure|array $key key name of the array element, an array of keys or property name of the object,
     * or an anonymous function returning the value. The anonymous function signature should be:
     * `function($array, $defaultValue)`.
     * The possibility to pass an array of keys is available since version 2.0.4.
     * @param mixed $default the default value to be returned if the specified array key does not exist. Not used when
     * getting value from an object.
     * @return mixed the value of the element if found, default value otherwise
     * @throws \Exception if $array is neither an array nor an object.
     */
    public function getValue($array, $key, $default = null)
    {
        if ($key instanceof \Closure) {
            return $key($array, $default);
        }

        if (is_array($key)) {
            $lastKey = array_pop($key);
            foreach ($key as $keyPart) {
                $array = $this->getValue($array, $keyPart);
            }
            $key = $lastKey;
        }

        if (is_array($array) && array_key_exists($key, $array)) {
            return $array[$key];
        }

        if (($pos = strrpos($key, '.')) !== false) {
            $array = $this->getValue($array, substr($key, 0, $pos), $default);
            $key = substr($key, $pos + 1);
        }

        if (is_object($array)) {
            return $array->$key;
        } elseif (is_array($array)) {
            return array_key_exists($key, $array) ? $array[$key] : $default;
        } else {
            return $default;
        }
    }

    /**
     * Removes an item from an array and returns the value. If the key does not exist in the array, the default value
     * will be returned instead.
     *
     * Usage examples,
     *
     * ~~~
     * // $array = ['type' => 'A', 'options' => [1, 2]];
     * // working with array
     * $type = \yii\helpers\ArrayHelper::remove($array, 'type');
     * // $array content
     * // $array = ['options' => [1, 2]];
     * ~~~
     *
     * @param array $array the array to extract value from
     * @param string $key key name of the array element
     * @param mixed $default the default value to be returned if the specified key does not exist
     * @return mixed|null the value of the element if found, default value otherwise
     */
    public function remove(&$array, $key, $default = null)
    {
        if (is_array($array) && (isset($array[$key]) || array_key_exists($key, $array))) {
            $value = $array[$key];
            unset($array[$key]);

            return $value;
        }

        return $default;
    }

    /**
     * Indexes an array according to a specified key.
     * The input array should be multidimensional or an array of objects.
     *
     * The key can be a key name of the sub-array, a property name of object, or an anonymous
     * function which returns the key value given an array element.
     *
     * If a key value is null, the corresponding array element will be discarded and not put in the
     * result.
     *
     * For example,
     *
     * ~~~
     * $array = [
     *     ['id' => '123', 'data' => 'abc'],
     *     ['id' => '345', 'data' => 'def'],
     * ];
     * $result = ArrayHelper::index($array, 'id');
     * // the result is:
     * // [
     * //     '123' => ['id' => '123', 'data' => 'abc'],
     * //     '345' => ['id' => '345', 'data' => 'def'],
     * // ]
     *
     * // using anonymous function
     * $result = ArrayHelper::index($array, function ($element) {
     *     return $element['id'];
     * });
     * ~~~
     *
     * @param array $array the array that needs to be indexed
     * @param string|\Closure $key the column name or anonymous function whose result will be used
     *     to index the array
     *
     * @return array the indexed array
     * @throws \Exception
     */
    public function index($array, $key)
    {
        $result = [];
        foreach ($array as $element) {
            $value = $this->getValue($element, $key);
            $result[$value] = $element;
        }

        return $result;
    }

    /**
     * Returns the values of a specified column in an array.
     * The input array should be multidimensional or an array of objects.
     *
     * For example,
     *
     * ~~~
     * $array = [
     *     ['id' => '123', 'data' => 'abc'],
     *     ['id' => '345', 'data' => 'def'],
     * ];
     * $result = ArrayHelper::getColumn($array, 'id');
     * // the result is: ['123', '345']
     *
     * // using anonymous function
     * $result = ArrayHelper::getColumn($array, function ($element) {
     *     return $element['id'];
     * });
     * ~~~
     *
     * @param array $array
     * @param string|\Closure $name
     * @param boolean $keepKeys whether to maintain the array keys. If false, the resulting array
     * will be re-indexed with integers.
     *
     * @return array the list of column values
     * @throws \Exception
     */
    public function getColumn($array, $name, $keepKeys = true)
    {
        $result = [];
        if ($keepKeys) {
            foreach ($array as $k => $element) {
                $result[$k] = $this->getValue($element, $name);
            }
        } else {
            foreach ($array as $element) {
                $result[] = $this->getValue($element, $name);
            }
        }

        return $result;
    }

    /**
     * Builds a map (key-value pairs) from a multidimensional array or an array of objects.
     * The `$from` and `$to` parameters specify the key names or property names to set up the map.
     * Optionally, one can further group the map according to a grouping field `$group`.
     *
     * For example,
     *
     * ~~~
     * $array = [
     *     ['id' => '123', 'name' => 'aaa', 'class' => 'x'],
     *     ['id' => '124', 'name' => 'bbb', 'class' => 'x'],
     *     ['id' => '345', 'name' => 'ccc', 'class' => 'y'],
     * ];
     *
     * $result = ArrayHelper::map($array, 'id', 'name');
     * // the result is:
     * // [
     * //     '123' => 'aaa',
     * //     '124' => 'bbb',
     * //     '345' => 'ccc',
     * // ]
     *
     * $result = ArrayHelper::map($array, 'id', 'name', 'class');
     * // the result is:
     * // [
     * //     'x' => [
     * //         '123' => 'aaa',
     * //         '124' => 'bbb',
     * //     ],
     * //     'y' => [
     * //         '345' => 'ccc',
     * //     ],
     * // ]
     * ~~~
     *
     * @param array $array
     * @param string|\Closure $from
     * @param string|\Closure $to
     * @param string|\Closure $group
     *
     * @return array
     * @throws \Exception
     */
    public function map(array $array, $from, $to, $group = null)
    {
        $result = [];
        foreach ($array as $element) {
            $key = $this->getValue($element, $from);
            $value = $this->getValue($element, $to);
            if ($group !== null) {
                $result[$this->getValue($element, $group)][$key] = $value;
            } else {
                $result[$key] = $value;
            }
        }

        return $result;
    }

    /**
     * Checks if the given array contains the specified key.
     * This method enhances the `array_key_exists()` function by supporting case-insensitive
     * key comparison.
     * @param string $key the key to check
     * @param array $array the array with keys to check
     * @param boolean $caseSensitive whether the key comparison should be case-sensitive
     * @return boolean whether the array contains the specified key
     */
    public static function keyExists($key, array $array, bool $caseSensitive = true): bool
    {
        if ($caseSensitive) {
            return \array_key_exists($key, $array);
        }

        foreach (\array_keys($array) as $k) {
            if (strcasecmp($key, $k) === 0) {
                return true;
            }
        }

        return false;
    }

    /**
     * Sorts an array of objects or arrays (with the same structure) by one or several keys.
     * @param array $array the array to be sorted. The array will be modified after calling this method.
     * @param string|\Closure|array $key the key(s) to be sorted by. This refers to a key name of the sub-array
     * elements, a property name of the objects, or an anonymous function returning the values for comparison
     * purpose. The anonymous function signature should be: `function($item)`.
     * To sort by multiple keys, provide an array of keys here.
     * @param integer|array $direction the sorting direction. It can be either `SORT_ASC` or `SORT_DESC`.
     * When sorting by multiple keys with different sorting directions, use an array of sorting directions.
     * @param integer|array $sortFlag the PHP sort flag. Valid values include
     * `SORT_REGULAR`, `SORT_NUMERIC`, `SORT_STRING`, `SORT_LOCALE_STRING`, `SORT_NATURAL` and `SORT_FLAG_CASE`.
     * Please refer to [PHP manual](http://php.net/manual/en/function.sort.php)
     * for more details. When sorting by multiple keys with different sort flags, use an array of sort flags.
     * @throws \Exception if the $direction or $sortFlag parameters do not have
     * correct number of elements as that of $key.
     */
    public function multisort(&$array, $key, $direction = SORT_ASC, $sortFlag = SORT_REGULAR)
    {
        $keys = is_array($key) ? $key : [$key];
        if (empty($keys) || empty($array)) {
            return;
        }
        $n = count($keys);
        if (is_scalar($direction)) {
            $direction = array_fill(0, $n, $direction);
        } elseif (count($direction) !== $n) {
            throw new \Exception('The length of $direction parameter must be the same as that of $keys.');
        }
        if (is_scalar($sortFlag)) {
            $sortFlag = array_fill(0, $n, $sortFlag);
        } elseif (count($sortFlag) !== $n) {
            throw new \Exception('The length of $sortFlag parameter must be the same as that of $keys.');
        }
        $args = [];
        foreach ($keys as $i => $key) {
            $flag = $sortFlag[$i];
            $args[] = $this->getColumn($array, $key);
            $args[] = $direction[$i];
            $args[] = $flag;
        }
        $args[] = &$array;
        call_user_func_array('array_multisort', $args);
    }

    /**
     * Encodes special characters in an array of strings into HTML entities.
     * Only array values will be encoded by default.
     * If a value is an array, this method will also encode it recursively.
     * Only string values will be encoded.
     * @param array $data data to be encoded
     * @param boolean $valuesOnly whether to encode array values only. If false,
     * both the array keys and array values will be encoded.
     * @param string $charset the charset that the data is using. If not set,
     * [[\yii\base\Application::charset]] will be used.
     * @return array the encoded data
     * @see http://www.php.net/manual/en/function.htmlspecialchars.php
     */
    public static function htmlEncode($data, $valuesOnly = true, $charset = 'UTF-8')
    {

        $d = [];
        foreach ($data as $key => $value) {
            if (!$valuesOnly && is_string($key)) {
                $key = htmlspecialchars($key, ENT_QUOTES, $charset);
            }
            if (is_string($value)) {
                $d[$key] = htmlspecialchars($value, ENT_QUOTES, $charset);
            } elseif (is_array($value)) {
                $d[$key] = self::htmlEncode($value, $valuesOnly, $charset);
            } else {
                $d[$key] = $value;
            }
        }

        return $d;
    }

    /**
     * Decodes HTML entities into the corresponding characters in an array of strings.
     * Only array values will be decoded by default.
     * If a value is an array, this method will also decode it recursively.
     * Only string values will be decoded.
     * @param array $data data to be decoded
     * @param boolean $valuesOnly whether to decode array values only. If false,
     * both the array keys and array values will be decoded.
     * @return array the decoded data
     * @see http://www.php.net/manual/en/function.htmlspecialchars-decode.php
     */
    public static function htmlDecode($data, $valuesOnly = true)
    {
        $d = [];
        foreach ($data as $key => $value) {
            if (!$valuesOnly && is_string($key)) {
                $key = htmlspecialchars_decode($key, ENT_QUOTES);
            }
            if (is_string($value)) {
                $d[$key] = htmlspecialchars_decode($value, ENT_QUOTES);
            } elseif (is_array($value)) {
                $d[$key] = self::htmlDecode($value);
            } else {
                $d[$key] = $value;
            }
        }

        return $d;
    }

    /**
     * @param array $arr
     *
     * @return bool
     */
    public static function isAssoc(array $arr): bool
    {
        if ([] === $arr) {
            return false;
        }

        return \array_keys($arr) !== range(0, count($arr) - 1);
    }

    /**
     * Returns a value indicating whether the given array is an associative array.
     *
     * An array is associative if all its keys are strings. If `$allStrings` is false,
     * then an array will be treated as associative if at least one of its keys is a string.
     *
     * Note that an empty array will NOT be considered associative.
     *
     * @param array $array the array being checked
     * @param boolean $allStrings whether the array keys must be all strings in order for
     * the array to be treated as associative.
     * @return boolean whether the array is associative
     */
    public static function isAssociative($array, $allStrings = true): bool
    {
        if (!\is_array($array) || empty($array)) {
            return false;
        }

        if ($allStrings) {
            foreach ($array as $key => $value) {
                if (!\is_string($key)) {
                    return false;
                }
            }
            return true;
        }

        foreach ($array as $key => $value) {
            if (\is_string($key)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Returns a value indicating whether the given array is an indexed array.
     *
     * An array is indexed if all its keys are integers. If `$consecutive` is true,
     * then the array keys must be a consecutive sequence starting from 0.
     *
     * Note that an empty array will be considered indexed.
     *
     * @param array $array the array being checked
     * @param boolean $consecutive whether the array keys must be a consecutive sequence
     * in order for the array to be treated as indexed.
     * @return boolean whether the array is associative
     */
    public static function isIndexed($array, $consecutive = false): bool
    {
        if (!\is_array($array)) {
            return false;
        }

        if (empty($array)) {
            return true;
        }

        if ($consecutive) {
            return array_keys($array) === range(0, \count($array) - 1);
        }

        foreach ($array as $key => $value) {
            if (!is_int($key)) {
                return false;
            }
        }

        return true;
    }

    /**
     * Returns a key for desired value
     * @param array $array
     * @param int|string $key
     *
     * @return false|int|string
     */
    public static function getKeyByValue(array $array, $key)
    {
        return \array_search($key, $array, true);
    }

    /**
     * Иногда array_diff не подходит. Например, когда надо сравнить заданный массив с [true,false,null]
     *
     * Два элемента считаются одинаковыми тогда и только тогда, если (string) $elem1 === (string) $elem2.
     * Другими словами, когда их строковое представление идентично.
     * http://php.net/manual/ru/function.array-diff.php
     *
     * @param array $arrayA
     * @param array $arrayB
     *
     * @return bool
     */
    public static function areIdenticalByValuesStrict(array $arrayA,array $arrayB): bool
    {
        if (\count($arrayA) !== \count($arrayB)) {
            return false;
        }
        foreach($arrayA as $value) {
            if (!\in_array($value,$arrayB,true)) {
                return false;
            }
        }
        return true;
    }

    /**
     * Получить первый элемент массива
     * @param array $array
     *
     * @return array|mixed|null
     */
    public static function firstValue( array $array )
    {

        if ( \is_array( $array ) ) {
            if ( \count($array) ) {
                reset($array);
                return current($array);
            }

            return null;

        }

        // TODO: если это не массив, то возвращаем прямо его же
        return $array;

    }

    /**
     * Получить первый элемент массива вместе с ключом в виде массива [<key> => <value>]
     * @param array $array
     *
     * @return array|mixed|null
     */
    public static function firstKeyWithValue( array $array ): array
    {

        if ( \count($array) ) {
            reset($array);
            return \array_slice($array,0,1,true);
            //return [key($array) => current($array)];
        }

        return [];

    }

    /*
     * Проиндексировать массив объектов/массивов по анонимной функции
     */
    public static function indexByClosure(array $array, \Closure $keyClosure): array
    {
        $indexedArray = [];
        foreach ($array as $item) {
            $indexedArray[$keyClosure($item)] = $item;
        }
        return $indexedArray;
    }

    /**
     * @param int[] $array
     *
     * @return int[]
     */
    public static function uniqueInt(array $array = [])
    {
        return array_filter(array_unique(array_map('intval', $array)));
    }

    /**
     * this helper is useful for PHP <7.3
     * https://www.php.net/manual/ru/function.array-push.php
     *
     * @param array $array
     * @param mixed ...$values
     */
    public static function push(array &$array, ...$values): void
    {
        if (!\count($values)) {
            return;
        }
        \array_push($array, ...$values);
    }

}
