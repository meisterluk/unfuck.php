<?php
    declare(encoding='utf-8');

    // @config  adjust to your own needs
    setlocale(LC_TIME, 'en_US.UTF-8');
    date_default_timezone_set('America/New_York');

    // @config
    // E_ERROR, E_WARNING,  E_PARSE, E_NOTICE, E_CORE_ERROR,
    // E_CORE_WARNING, E_COMPILE_ERROR, E_COMPILE_WARNING,
    // E_USER_ERROR, E_USER_WARNING, E_USER_NOTICE, E_STRICT
    // E_RECOVERABLE_ERROR, E_DEPRECATED, E_USER_DEPRECATED
    // E_ALL
    error_reporting(E_ALL | E_STRICT);
    ini_set('display_errors', true);

    //
    // A short alias to escape HTML consistently.
    //
    // @param string text  the text to escape
    // @return string  the escaped string
    //
    function _e($text)
    {
        // @config  ENT_QUOTES if _e() is applied to XML attributes
        return htmlspecialchars($text, ENT_COMPAT);
    }

    //
    // Get item from the array or return default value.
    //
    // @param array  the array
    // @param key  the key to look for
    // @param default  the default value
    // @return mixed  either array[key] or default value
    //
    function getItem($array, $key, $default=NULL)
    {
        return array_key_exists($key, $array) ? $array[$key] : $default;
    }

    //
    // Titlecase words in the given string
    // (ie. ucwords() but also look for hyphens).
    // Looses all upper/lowercase information given by string.
    //
    // @param string string  the words
    // @return string  the capitalized input string
    //
    function Titlecase($string)
    {
        $string = ucwords(strtolower($string));
        $chars = array('-', '&', '/', '(', ')', '{', '}', '[', ']', '`', '´',
                       '<', '>');  // each.{ len == 1 }
        // all characters after any element in $chars or an whitespace
        // will be uppercased
        foreach ($chars as $char)
        {
            $index = 0;
            while ($index !== false)
            {
                if ($string[$index] === $char)
                    $string[$index+1] = strtoupper($string[$index+1]);
                $index = strpos($string, $char, $index+1);
            }
        }
        return $string;
    }

    //
    // CamelCase words in the given string.
    // Any whitespace, hyphen and dot at the end of a sentence will be removed.
    //
    // @param string string  the words
    // @return string  the camelcased input string
    //
    function camelCase($string)
    {
        $string = titlecase($string);
        $string = str_replace('‐', '', $string); // U+2010
        $string = str_replace('-', '', $string); // U+002D
        $string = str_replace('‑', '', $string); // U+2011
        $string = str_replace('⁃', '', $string); // U+2043
        $string = preg_replace('/(\w)\.(\s)/', '\1\2', $string);
        $string = preg_replace('/\s+/', '', $string);

        return $string;
    }

    //
    // Check whether parameter starts with a given substring or not.
    //
    // @param string string  parameter to search in
    // @param string substring  needle to search for
    // @return bool  string starts with substring
    //
    function startswith($string, $substring)
    {
        return substr($string, 0, strlen($substring)) === $substring;
    }

    //
    // Check whether parameter ends with a given substring or not.
    //
    // @param string string  parameter to search in
    // @param string substring  needle to search for
    // @return bool  string ends with substring
    //
    function endswith($string, $substring)
    {
        if ($substring === '')
            return true;
        return substr($string, -strlen($substring)) === $substring;
    }

    //
    // A simple empty() replacement.
    // Sorry, but code stands for itself.
    //
    // @param mixed val a value to parse
    // @return bool  test value to be considered as 'empty' or not
    //
    function isEmpty($val)
    {
        if (is_string($val))
        {
            if ($val === '')
                return true;
            return false;
        } elseif (is_bool($val)) {
            return false;
        } elseif (is_integer($val) || is_long($val)
            || is_float($val) || is_double($val)) {
            if ($val === 0)
                return true;
            if ($val === 0.0)
                return true;
            return false;
        } elseif (is_null($val)) {
            return true;
        } elseif (is_array($val)) {
            return (count($val) == 0);
        }
        return false;  // eg. object
    }

    //
    // Take some string ending with ' <number>'.
    // Return a string with <number> incremented.
    //
    // @param string string  the string
    // @return string  the string with number incremented or unchanged.
    //
    function inc(&$a)
    {
        assert(is_int($a));
        return ++$a;
    }
    function incrementSuffix($string)
    {
        $func = create_function('$match', '$int = (int)$match[2]; '.
                                'return $match[1].inc($int);');
        $result = preg_replace_callback('/(\s)(\d+)$/', $func, $string);
        if ($result === NULL)
            return $string;
        return $result;
    }

    //
    // Creates a unique ID among all calls in one program run.
    //
    // @return int  a unique ID
    //
    function uniqueId()
    {
        static $ids = array();
        while (in_array(($id = mt_rand()), $ids))
        {}
        $ids[] = $id;
        return $id;
    }

    //
    // Return a subset of the parameter array with only keys of the whitelist.
    //
    // @param array  the array to select from
    // @param whitelist  list of keys to select
    // @return array  a subset of array
    //
    function whitelist($array, $whitelist)
    {
        $new_array = array();
        foreach ($array as $key => $value)
        {
            if (in_array($key, $whitelist))
                $new_array[$key] = $value;
        }
        return $new_array;
    }

    //
    // Return a subset of the parameter array with only keys not in blacklist.
    //
    // @param array  the array to select from
    // @param blacklist  list of keys to throw away
    // @return array  a subset of array
    //
    function blacklist($array, $blacklist)
    {
        $new_array = array();
        foreach ($array as $key => $value)
        {
            if (!in_array($key, $blacklist))
                $new_array[$key] = $value;
        }
        return $new_array;
    }

    // Custom exceptions

    class UndefinedValueException extends InvalidArgumentException { }
    class LookupException extends LogicException { }
    class EnvironmentException extends RuntimeException { }
    class NotImplementedException extends RuntimeException { }
    class CharsetException extends RuntimeException { }

    //
    // A basic stack implementation.
    // This implementation uses object-based state for configuration.
    // Note. This class does not use PHP 5.5's generators.
    //
    // *list behavior*
    //   A real stack is one where you can only access the top element.
    //   Therefore the interface only provides push() and pop().
    //   However in practice access to other elements is helpful in some
    //   cases. So just like python's list, this datatype methods allow to
    //   interact with elements 'in the middle'.
    //
    // *stackorder / listorder*
    //   Assume you are getting a slice of the stack.
    //   Are the returned elements in their natural (order of pushing)
    //   or reversed order (order of popping)?
    //   You can configure this in the constructor
    //   and modify the property during runtime (if necessary).
    //   In general listorder is considered to be more intuitive.
    //
    // @method chunk($size)
    // @method clear()
    // @method __clone()
    // @method __construct($max_size=self::INFINITE_SIZE, $order=self::ORDER_LIST)
    // @method copy()
    // @method count()
    // @method create($max_size=NULL, $elements=NULL, $order=NULL, $name=NULL)
    // @method diff($stack)
    // @method equals($stack)
    // @method exists($value)
    // @method getName()
    // @method getStackSize()
    // @method index($index)
    // @method intersect($stack)
    // @method iterate()
    // @method iterateFiltered($callback, $method='callback')
    // @method iteratePopping()
    // @method map($callback)
    // @method pad($size, $value)
    // @method pop($count=1)
    // @method push($value)
    // @method pushRev($value)
    // @method pushElement($value)
    // @method reduce($callback)
    // @method replace($index, $replacement)
    // @method replaceElements($replacements)
    // @method reverse()
    // @method setName($name)
    // @method __set_state($properties)
    // @method shift($count=1)
    // @method shuffle()
    // @method slice($offset, $length=null)
    // @method splice($start, $end, $replacements)
    // @method sort($cmp_func=NULL)
    // @method __toString()
    // @method unique($sort_flags=SORT_REGULAR)
    // @method unshift($array)
    //
    class Stack {
        const ORDER_LIST = 1;
        const ORDER_STACK = 2;
        const INFINITE_SIZE = -1;

        protected $counter;
        protected $elements;
        protected $name;
        public $max_size;
        public $order;

        //
        // An interface towards php's array_chunk.
        // Splits the elements at the stack into equal-sized parts.
        // Order of chunks depend on configured order.
        //
        // @param int size  the size of each chunck
        // @return array  an array containing chunks of the stack elements
        //
        public function chunk($size)
        {
            assert(is_int($size));
            if ($this->order === self::ORDER_LIST)
                $elements = $this->elements;
            else
                $elements = array_reverse($this->elements, false);

            return array_chunk($elements, $size, false);
        }

        //
        // Clear the stack. In-place method.
        //
        // @return this
        //
        public function clear()
        {
            $this->elements = array();
            $this->counter = 0;
            return $this;
        }

        //
        // The magic __clone method (Object cloning).
        //
        // @return Stack  an cloned Stack instance.
        //
        public function __clone()
        {
            return $this->create($this->max_size, $this->elements,
                $this->order, $this->name);
        }

        //
        // Constructor.
        //
        // @param int max_size  the maximum size of the stack
        //                      (self::INFINITE_SIZE for INFINITE_SIZE)
        // @param boolean order  The order slices of elements are returned
        //                       (either ORDER_STACK or ORDER_LIST)
        //
        public function __construct($max_size=self::INFINITE_SIZE,
                $order=self::ORDER_LIST)
        {
            $this->max_size = $max_size;
            $this->order = $order;
            $this->counter = 0;
            $this->elements = array();
        }

        //
        // Copy the stack.
        // Create a new Stack instance with the same properties.
        // Alias for clone operator.
        //
        // @return Stack  the copy
        //
        public function copy()
        {
            return clone $this();
        }

        //
        // Get the size of the current stack.
        // Alias for getStackSize.
        //
        // @return int  the number of elements
        //
        public function count()
        {
            return $this->getStackSize();
        }

        //
        // A non-real cloning method.
        // It allows simple creation of new stack based upon the configuration
        // of the current object.
        //
        // @param max_size  a new max_size or NULL
        // @param elements  a new elements array or NULL
        // @param order  a new order property or NULL
        // @param name  a new name or NULL
        // @return Stack  a new stack
        //
        public function create($max_size=NULL, $elements=NULL, $order=NULL, $name=NULL)
        {
            if ($max_size === NULL)
                $max_size = $this->max_size;

            if ($order === NULL)
                $order = $this->order;

            $stack = new Stack($max_size, $order);

            if ($elements !== NULL)
                $stack->elements = $elements;
            else
                $stack->elements = $this->elements;
            $stack->counter = count($stack->elements);

            if ($name !== NULL)
                $stack->setName($name);
            else {
                if (is_numeric(substr($this->name, -1)))
                    $stack->setName(incrementSuffix($this->name));
                elseif (strlen($this->name) > 3)
                    $stack->setName($this->name.' 2');
                else
                    $stack->setName($this->name);
            }

            return $stack;
        }

        //
        // Get a new Stack with set of elements of current stack not available
        // in stack provided as argument.
        // Note. The position of the element is not important.
        // Note. The new stack inherits the configuration of the current stack.
        //
        // @param Stack|array stack  a Stack or array to compare with
        // @return Stack  a new Stack instance with elements of diff
        //
        public function diff($stack)
        {
            $new = $this->create();
            $new->clear();
            if (is_array($stack))
                $diff = array_diff($this->elements, $stack);
            else {
                $diff = array_diff($this->getNormalizedArray(),
                                   $stack->getNormalizedArray());
            }
            if (!isEmpty($diff))
                call_user_func_array(array($new, 'push'), $diff);
            return $new;
        }

        //
        // Get a new Stack with set of elements of current stack not available
        // in stack provided as argument.
        // Note. The position of the element matters!
        // Note. The new stack inherits the configuration of the current stack.
        //
        // @param Stack|array stack  a Stack or array to compare with
        // @return Stack  a new Stack instance with elements of diff
        //
        public function diffAssoc($stack)
        {
            $new = $this->create();
            $new->clear();
            if (is_array($stack))
                $diff = array_diff_assoc($this->elements, $stack);
            else {
                $diff = array_diff_assoc($this->getNormalizedArray(),
                                         $stack->getNormalizedArray());
            }
            if (!isEmpty($diff))
                call_user_func_array(array($new, 'push'), $diff);
            return $new;
        }

        //
        // Test equality with another stack.
        // Note. Does only test elements. Not configuration.
        //
        // @param Stack|array stack  a stack or array to compare with
        // @return boolean  is equal
        //
        public function equals($stack)
        {
            if (is_array($stack))
                return $this->getNormalizedArray() === $stack;
            else
                return ($this->iterate() === $stack->iterate());
        }

        //
        // Does $value exist as element in the stack?
        //
        // @param mixed value  the value to look for
        // @return bool  $value exists in stack?
        //
        public function exists($value)
        {
            return in_array($value, $this->elements);
        }

        //
        // Get max size.
        //
        // @return int|NULL  the maximum size. Integer or NULL (for infinite)
        //
        public function getMaxSize()
        {
            if ($this->max_size === Stack::INFINITE_SIZE)
                return NULL;
            else
                return $this->max_size;
        }

        //
        // Get the name assigned to this Stack.
        //
        // @return string  the name you (hopefully) assigned to this stack
        //
        public function getName()
        {
            return (string)$this->name;
        }

        //
        // Get a normalized representation of the array elements
        // for comparison among stacks.
        //
        // @return array  the normalized array
        //
        protected function getNormalizedArray()
        {
            if ($this->order !== self::ORDER_LIST)
                return array_reverse($this->elements);
            return $this->elements;
        }

        //
        // Get the size of the current stack.
        // Ie. the number of elements on the stack
        //
        // @return int  the number of elements
        //
        public function getStackSize()
        {
            assert($this->counter >= 0);
            return $this->counter;
        }

        //
        // Indexing.
        // Access element at $index or throw OutOfRangeException
        //
        // @param int index  the index you want to access
        // @return mixed  the element at this index
        //
        public function index($index)
        {
            assert(is_int($index));
            if ($index < $this->getStackSize())
            {
                if ($this->order !== self::ORDER_LIST)
                    $index = $this->getStackSize() - $index - 1;
                return $this->elements[$index];
            }

            $msg = 'Provided index for stack is out of range';
            throw new OutOfRangeException($msg);
        }

        //
        // Create a new stack with intersected elements between the
        // current stack and the stack provided as argument
        //
        // @param Stack|array stack  the stack or array to compare with
        // @return Stack  the stack with intersected elements
        //
        public function intersect($stack)
        {
            assert(is_array($stack) || is_a($stack, __CLASS__));

            $new = $this->create();
            $new->clear();
            $cmp_elements = is_array($stack) ? $stack : $stack->getNormalizedArray();

            $diff = array_intersect($this->getNormalizedArray(), $cmp_elements);
            if (!isEmpty($diff))
                call_user_func_array(array($new, 'push'), $diff);

            return $new;
        }

        //
        // Return an array for iteration according to configured order.
        //
        // @return array  an array you can iterate over
        //
        public function iterate()
        {
            if ($this->order === self::ORDER_LIST)
                return array_values($this->elements);
            else
                return array_reverse($this->elements, false);
        }

        //
        // Return an array for iteration according to configured order
        // but filter for elements with a callback or an array.
        //
        // @param array|callback callback  An array of elements or a
        //                                 callback to filter for
        // @param string method  the method (either 'callback' or 'array')
        // @return array  an array you can iterate over
        //
        public function iterateFiltered($callback, $method='callback')
        {
            assert(($method === 'array' && is_array($callback)) ||
                   ($method === 'callback' && is_callable($callback)));

            // Please remember that a callback might be an array
            // (static method). That's why $method exists.
            if ($method === 'callback')
                return array_filter($this->iterate(), $callback);

            return array_values($this->diff($callback)->iterate());
        }

        //
        // Return an object for iteration and pop elements at the same time.
        //
        // @return array  an array you can iterate over
        //
        public function iteratePopping()
        {
            $elements = $this->iterate();
            $this->clear();
            return $elements;
        }

        //
        // Apply a callback to each element of the stack. In-place method.
        //
        // @param callback callback  The callback to apply
        // @return this
        //
        public function map($callback)
        {
            assert(is_callable($callback));
            $this->elements = array_map($callback, $this->elements);
            return $this;
        }

        //
        // Array padding.
        // Adjust size of stack to $size by popping elements or
        // pushing (one or more) $value. In-place method.
        //
        // Note. This method behaves very much different than PHP's array_pad.
        //
        // @param int size  the target size
        // @param mixed value  the value to increase size
        // @return this
        //
        public function pad($size, $value)
        {
            assert(is_int($size));
            if ($size < 0)
                throw new InvalidArgumentException(
                    'size argument of pad(size, value) must be positive'
                );

            if ($size > $this->getStackSize())
            {
                $this->elements = array_pad($this->elements, $size, $value);
                $this->counter = $size;

            } else if ($size === $this->getStackSize()) {
                // nothing.

            } else {
                $diff = $this->getStackSize() - $size;
                $this->pop($diff);
                $this->counter = $size;
            }
            return $this;
        }

        //
        // Pop one or more elements of the stack.
        //
        // Note. Unlike indexing the returned elements will be removed
        //       from the stack.
        // Note. The order of the returned elements depends on the
        //       configured order for the stack.
        //
        // Throws an UnderflowException for popping from empty stack.
        //
        // @param int count  how many elements shall be popped?
        // @return mixed  a value (if $count===1) or an array of elements
        //
        public function pop($count=1)
        {
            assert(is_int($count));
            if ($count === 0)
                return;
            if ($this->counter < $count)
                throw new UnderflowException('Popping too many arguments');

            $elements = array_slice($this->elements, -$count,
                                    $count, false);

            if ($this->order === self::ORDER_STACK)
                $elements = array_reverse($elements, false);

            $this->elements = array_slice($this->elements, 0, -$count, false);
            $this->counter -= $count;

            if ($count == 1)
                return $elements[0];
            else
                return $elements;
        }

        //
        // Add one or more values to the top of the stack.
        // An OverflowException might occur. In-place method.
        //
        // Note. Independent of the configured order, the left-most
        //       value gets pushed first. So the order will be preserved.
        // Note. Use pushRev() for the reversed order.
        // Note. Use pushElement() for the order configured in the
        //       constructor.
        // Note. Variadic function.
        //
        // @param mixed value  the value to push to the stack
        // @return this
        //
        public function push($value)
        {
            if (func_num_args() > 1)
            {
                $args = func_get_args();
                $this->SOcheck('Failed to push values', count($args));
                $this->elements = array_merge($this->elements, $args);
                $this->counter += count($args);

            } else {
                $this->SOcheck('Failed to push value', 1);
                $this->elements[] = $value;
                $this->counter += 1;
            }

            return $this;
        }

        //
        // Add one or more values to the top of the stack in reversed
        // order. In-place method.
        //
        // Note. Independent of the configured order, the left-most
        //       value gets pushed last. So the order will be reversed.
        // Note. See also push()
        // Note. Variadic function.
        //
        // @param mixed value  the value to push to the stack
        // @return this
        //
        public function pushRev($value)
        {
            if (func_num_args() > 1)
            {
                $args = array_reverse(func_get_args());
                $this->SOcheck('Failed to push values', count($args));
                $this->elements = array_merge($this->elements, $args);
                $this->counter += count($args);

            } else {
                $this->SOcheck('Failed to push value', 1);
                $this->elements[] = $value;
                $this->counter += 1;
            }

            return $this;
        }

        //
        // Add one or more values to the top of the stack.
        // The order depends on the configured order in the constructor.
        // In-place method.
        //
        // Note. See also push()
        // Note. Variadic function.
        //
        // @param mixed value  the value to push
        // @return this
        //
        public function pushElement($value)
        {
            if ($this->order === self::ORDER_LIST)
                call_user_func_array(array($this, 'push'),
                                     func_get_args());
            else
                call_user_func_array(array($this, 'pushRev'),
                                     func_get_args());
            return $this;
        }

        //
        // A reduce function in functional programming takes a callback
        // and a list of elements. It applies the callback (2 arguments)
        // to the first two elements of the list and afterwards
        // cumulatively to the previous result and the next element.
        //
        // Note. The order of evaluation depends on the configured order
        //       in the constructor.
        // Note. Returns null if stack is empty.
        // Note. Does not modify the stack.
        //
        // @param callback callback  A callback to perform reduce()
        // @return mixed  the result of the reduce() given by the callback
        //
        public function reduce($callback)
        {
            assert(is_callable($callback));

            if ($this->counter === 0)
                return null;
            if ($this->counter === 1)
                return $this->elements[0];

            $elements = $this->elements;
            if ($this->order === self::ORDER_STACK)
                $elements = array_reverse($elements);

            $result = $elements[0];
            foreach (array_slice($elements, 1) as $value)
                $result = call_user_func($callback, $result, $value);

            return $result;
        }

        //
        // Replace value at $index with $replacement. In-place method.
        // Throws an OutOfRangeException if index does not exist.
        //
        // @param int index  the index to look for
        // @param mixed replacement  the value to insert
        // @return this
        //
        public function replace($index, $replacement)
        {
            assert(is_int($index));
            if (array_key_exists($index, $this->elements))
                $this->elements[$index] = $replacement;
            else
                throw new OutOfRangeException('Element to replace is out of range');
            return $this;
        }

        //
        // Replace elements using the associative array $replacements.
        // In-place method.
        //
        // @param array replacements  an associative array with keys already
        //                            pushed at stack to be replaced by the
        //                            corresponding value
        // @return $this
        //
        public function replaceElements($replacements)
        {
            assert(is_array($replacements));
            foreach ($this->elements as $key => $value)
            {
                if (array_key_exists($value, $replacements))
                    $this->elements[$key] = $replacements[$value];
            }
            return $this;
        }

        //
        // Reverse the stack. In-place method.
        //
        // @return this
        //
        public function reverse()
        {
            $this->elements = array_reverse($this->elements, false);
            return $this;
        }

        //
        // Set a name. Provide better messages in error handling.
        // In-place method.
        //
        // @param string name  a name for the stack
        // @return this
        //
        public function setName($name)
        {
            $this->name = (string)$name;
            return $this;
        }

        //
        // The magic __set_state method.
        //
        // @param array properties  the properties to set
        // @return object  an object Stack with properties set to $properties
        //
        public static function __set_state($properties)
        {
            $stack = new Stack();
            $stack->max_size = $properties['max_size'];
            $stack->counter = $properties['counter'];
            $stack->elements = $properties['elements'];
            $stack->order = $properties['order'];
            $stack->name = $properties['name'];

            return $stack;
        }

        //
        // pop() for the bottom of the stack.
        // Will remove $count elements from the bottom of the stack.
        //
        // Note. Unlike indexing the returned elements will be removed
        //       from the stack.
        // Note. The order of the returned elements depends on the
        //       configured order for the stack.
        //
        // Throws an UnderflowException for shifting from empty stack.
        //
        // @param int count  how many elements shall be shifted?
        // @return mixed  a value (if $count===1) or an array of elements
        //
        public function shift($count=1)
        {
            assert(is_int($count));

            if ($this->counter < $count)
                throw new UnderflowException('Shifting too many arguments');

            $elements = array_slice($this->elements, 0, $count);

            if ($this->order === self::ORDER_STACK)
                $elements = array_reverse($elements, false);

            $this->elements = array_slice($this->elements, $count);
            $this->counter -= $count;

            return $elements;
        }

        //
        // Shuffle stack. In-place method.
        //
        // @return bool  boolean indicating success or failure
        //
        public function shuffle()
        {
            return shuffle($this->elements);
        }

        //
        // Slice the stack.
        //
        // Note. If $length surpasses the border of the stack,
        //       the length of the result will be smaller than $length
        //
        // @param int offset  the offset for the slice
        // @param int|null length  length of return value or until end
        // @return array  array of sliced elements
        //
        public function slice($offset, $length=null)
        {
            if ($this->order === self::ORDER_STACK)
                $elements = array_reverse($this->elements, false);
            else
                $elements = $this->elements;

            if (is_null($length))
                $elements = array_slice($elements, $offset);
            else
                $elements = array_slice($elements, $offset, $length);

            return $elements;
        }

        //
        // Take some slice and replace elements of slice with
        // $replacements. In-place method.
        //
        // Note. The interval [$start, $end) must not cross the end of
        //       the array. Throw a RangeException in case.
        // Note. According to set builder notation the $start is
        //       inclusive; the end is exclusive.
        //
        // @param int start  the start index of the slice
        // @param int end  the end index of the slice
        // @param array replacements  elements inserted instead
        // @return this
        //
        public function splice($start, $end, $replacements)
        {
            if ($start < 0)
                $start = count($this->elements) + $start;
            if ($end < 0)
                $end = count($this->elements) + $end;

            if ($start === $end)
                return $this->replace($start, $replacements[0]);

            if ($start > $end)
                throw new RangeException('Cannot splice above Stack bounds');

            $head = array_slice($this->elements, 0, $start);
            $tail = array_slice($this->elements, $end);

            $this->elements = array_merge($head, $replacements, $tail);
            return $this;
        }

        //
        // Sort elements of the stack. In-place method.
        // Note. You might wanna use iterate() or pop() afterwards.
        //
        // @param callback cmp_func  a custom comparison function
        // @return this
        //
        public function sort($cmp_func=NULL)
        {
            if (is_null($cmp_func))
                sort($this->elements);
            else
                usort($this->elements, $cmp_func);
            return $this;
        }

        //
        // Test whether or not a stackoverflow occurs or is going to occur.
        //
        // If $additionals is not provided it will check the current stack
        // for a stack overflow. If $additional is provided it assumes you
        // are going to push $additionals elements to the stack and tests
        // the overflow after the hypothetical push.
        // Note. $additionals might be negative.
        //
        // Throws an OverflowException or UnderflowException in case.
        //
        // @param string msg  the message to throw OverflowException with
        //                    (should describe what you where currently doing)
        // @param int additionals  how many elements are going to be
        //                         pushed to the array?
        //
        protected function SOcheck($msg, $additionals=0)
        {
            if ($this->max_size == self::INFINITE_SIZE)
                return;

            $size = $this->counter + (int)$additionals;

            if (isEmpty($this->name))
                $name = '';
            else
                $name = '[Stack '.$this->name.']';

            if ($size > $this->max_size)
            {
                $overflow = '[size %d > %d]';
                $overflow = sprintf($overflow, $size, $this->max_size);

                $error_msg = implode(' ', array($name, $overflow, $msg));
                throw new OverflowException($error_msg);

            } elseif (0 > $size) {
                $overflow = '[size 0 > %d]';
                $overflow = sprintf($overflow, $size);

                $error_msg = implode(' ', array($name, $overflow, $msg));
                throw new OverflowException($error_msg);
            }
        }

        //
        // String representation of the stack.
        // Magic method __toString.
        //
        // @return string  the string representation
        //
        public function __toString()
        {
            $elements = $this->elements;
            foreach ($elements as $key => $element)
            {
                if (is_string($element))
                    $elements[$key] = '"'.$element.'"';
                else
                    $elements[$key] = str_replace("\n", ' ',
                        var_export($element, true));
            }

            if (isEmpty($this->name))
                $str = '[ ';
            else
                $str = ':'.$this->name.':[ ';

            $str .= implode(' | ', $elements);
            $str .= ' ]';
            return $str;
        }
        //
        // Remove duplicate values from stack. In-place method.
        //
        // [0] http://us.php.net/manual/de/function.array-unique.php
        //
        // @param int sort_flags  Flags defining comparison configuration
        //                        see also [0]
        // @return this
        //
        public function unique($sort_flags=SORT_REGULAR)
        {
            $this->elements = array_unique($this->elements, $sort_flags);
            $this->counter = count($this->elements);
            return $this;
        }

        //
        // Will insert $array at the bottom of the stack. In-place method.
        // Note. It will be inserted in the configured order.
        //
        // @param array array  an array to shift
        // @return this
        //
        public function unshift($array)
        {
            $this->SOcheck('Cannot unshift so many elements',
                            count($array));

            if ($this->order === self::ORDER_STACK)
                $array = array_reverse($array, false);

            $this->elements = array_merge($array, $this->elements);
            $this->counter = count($this->elements);
            return $this;
        }
    }

    //
    // Notification class
    //
    // A very simple user-level notifications class
    // It handles (message, class) pairs. Used for error messages.
    //
    // Note. If you dislike it, look for trigger_error() or error_log()
    //
    // Possible "classes" {
    //    0: Note.
    //    2: Warning.
    //    3: Error.
    //  }
    //
    // Interface:
    //
    // @method push($msg, $class=3)
    // @method pop()
    // @method count()
    // @method reset()
    // @method filter($min=3, $gt=true)
    // @method iterate()
    // @method dump($format='text', $indent=0)
    //
    class Notifications
    {
        const NOTE = 0;
        const INFO = 1;
        const WARN = 2;
        const ERROR = 3;

        protected $msgs;

        //
        // Constructor.
        //
        public function __construct()
        {
            $this->msgs = new Stack();
        }

        //
        // Add message with associated class
        //
        // @param string msg  the message to store
        // @param int class  optional, the class (integer)
        // @return false  always returns false!
        //                So it can be used directly in return contexts
        //
        public function push($msg, $class=3)
        {
            $this->msgs->push(array($msg, $class));
            return false;
        }

        //
        // Pop last message from list.
        //
        // @return array  the popped value with array(msg, class)
        //
        public function pop()
        {
            return $this->msgs->pop();
        }

        //
        // Get number of messages on stack.
        //
        // @return int  the number of messages stored
        //
        public function count()
        {
            return $this->msgs->count();
        }

        //
        // Reset message stack.
        //
        public function reset()
        {
            $this->msgs->clear();
        }

        //
        // A comparison method for sorting
        // (compares classes [in current implementation])
        //
        // @param array msg1  the first message
        // @param array msg2  the second message
        // @return int  an integer indicating difference of msg1 and msg2
        //
        static public function _class_cmp($msg1, $msg2)
        {
            if ($msg1[1] < $msg2[1])
                return -1;
            elseif ($msg1[1] === $msg2[1])
                return 0;
            else
                return 1;
        }

        //
        // A filtering method
        //
        // @param int min  a minimum value the class has to be
        // @param int gt  test for class >= min on true, <= on false
        // @return array  an array with array(msg, class) values
        //
        public function filter($min=WARN, $gt=true)
        {
            $result = array();
            foreach ($this->msgs->iterate() as $value)
            {
                if (($gt && $value[1] >= $min) || (!$gt && $value[1] <= $min))
                    $result[] = $value;
            }
            return $result;
        }

        //
        // Translate classes
        // Takes a class and replaces it with associated value in given
        // classes parameter. Check out the method's source code for
        // the names of CSS classes (default classes parameter).
        //
        // Use the iterate() method to iterate over the result.
        //
        // @param array classes  a map between source classes
        //                       and translated classes
        //
        protected function translateClasses($classes=NULL)
        {
            if (is_null($classes))
                $classes = array(
                    self::NOTE => 'note',
                    self::INFO => 'info',
                    self::WARN => 'warn',
                    self::ERROR => 'error'
                );

            foreach ($this->msgs as $key => $value)
            {
                if (is_int($value[1]))
                    $this->msgs[$key][1] = $classes[$value[1]];
            }
        }

        //
        // Iteration method
        // Returns array messages. Some kind of a getter method with
        // pre-sorting capabilities.
        //
        // @return array  array of messages
        //
        public function iterate()
        {
            $this->msgs->sort(array($this, '_class_cmp'));

            return $this->msgs->iterate();
        }

        //
        // Dump in HTML or plain text.
        //
        // @param string format  output format. either 'text' or 'html'
        // @param int indent  the level of indentation
        //                    (ie. number of spaces before each line)
        // @return string  a string describing the state of the object
        //
        public function dump($format='text', $indent=0)
        {
            $in = str_repeat(' ', $indent);
            $out = '';
            $this->translateClasses();

            switch ($format)
            {
            case 'html':
                $out .= $in.'<div class="dump">'."\n";
                $out .= $in.'  <h1>Notifications Dump</h1>'."\n\n";
                $out .= $in.'  <ul>'."\n";
                foreach ($this->msgs->iterate() as $msg)
                {
                    $out .= $in.'    <li class="'._e($msg[1]).'">'
                            ._e($msg[0]).'</li>'."\n";
                }
                $out .= $in.'  </ul>'."\n".$in.'</div>'."\n";
                break;

            default:
            case 'text':
                $out .= $in."Notifications Dump\n";
                $out .= $in."==================\n\n";
                $found = false;
                foreach ($this->msgs->iterate() as $msg)
                {
                    $out .= $in.' * Message (class='.$msg[1].'):'.$msg[0]."\n";
                    $found = true;
                }
                if (!$found)
                    $out .= $in.'(no notifications stored).';
                break;
            }
            return $out;
        }

        //
        // Magic method. String representation.
        //
        public function __toString()
        {
            return $this->dump('text', 0);
        }
    }

    //
    // Sanitizor class.
    // If you dislike this class, you might want to check out the
    // filter_var function of the "Filter Functions" module in PHP5.
    //
    // Note. This is not a pure singleton class. It still allows instantiation,
    //       but a singleton can be accessed by the getInstance() method as well
    // Note. This class does not support arrays as parameters. To process
    //       one-dimensional arrays, you can set the array to be a context,
    //       but input is supposed to be flat (not hierarchical) here.
    // Note. In current implementation, if variable occurs in multiple contexts
    //       the first $context containing the value is used.
    //
    // Hooks
    //   The Sanitizor provides several hooks. These are defined states reached
    //   during the evaluation of a parameter. You might want to inherit this
    //   class and override the hooks with the subclass for your desired behavior.
    //
    //
    // Public API:
    //
    // @method __construct($options=NULL)
    // @method getInstance($options=NULL)
    //
    // @method setUseDefaultsInvalid($use_defaults_invalid)
    // @method getUseDefaultsInvalid()
    // @method setUseDefaultsUndefined($use_defaults_undefined)
    // @method getUseDefaultsUndefined()
    // @method setName($name)
    // @method getName()
    //
    // @method preProcessingHook($identifier, $value)
    // @method postProcessingHook($identifier, $value)
    // @method invalidValueHook($identifier, $value)
    // @method undefinedValueHook($identifier)
    // @method noDefaultValueHook($identifier)
    //
    // @method dump($format='text', $indent=0)
    //
    // @method addContext($array, $id=NULL, $overwrite=true)
    // @method removeContext($identifier)
    // @method clearContexts()
    //
    // @method addFilter($identifier, $filter, $parameters=NULL)
    // @method registerFilter($name, $callback)
    // @method removeFilters($identifier)
    // @method clearFilters()
    //
    // @method addRule($identifier, $types=NULL, $default=NULL, $overwrite=true)
    // @method removeRule($identifier)
    // @method clearRules()
    //
    // @method getValidity($identifier)
    // @method getParameter($identifier)
    // @method get($identifier)
    // @method __get($identifier)
    //
    class Sanitizor
    {
        const FILTER_LOWER              = 1;
        const FILTER_UPPER              = 2;
        const FILTER_BETWEEN            = 4;
        const FILTER_MEMBER             = 8;
        const FILTER_MAXLENGTH          = 16;
        const FILTER_TRIM               = 32;
        const FILTER_TITLECASE          = 64;
        const FILTER_CAMELCASE          = 128;

        const TYPE_NULL                 = 1;
        const TYPE_INTEGER              = 2;
        const TYPE_HEX                  = 4;
        const TYPE_STRING               = 8;
        const TYPE_FLOAT                = 16;
        const TYPE_BINARY               = 32;
        const TYPE_BOOL                 = 64;
        const TYPE_LOOSE_BOOL           = 128;
        const TYPE_CHAR                 = 256;
        const TYPE_ALNUM                = 512;
        const TYPE_ALPHA                = 1024;
        const TYPE_PRINT                = 2048;
        const TYPE_WHITESPACE           = 4096;

        protected $default_null         = NULL;
        protected $default_integer      = 0;
        protected $default_hex          = 0;
        protected $default_string       = '';
        protected $default_float        = 1.0;
        protected $default_binary       = 0;
        protected $default_bool         = false;
        // loose boolean always evaluates to true or false
        #protected $default_loose_bool   = false;
        protected $default_char         = '0';
        protected $default_alnum        = '0';
        protected $default_alpha        = 'A';
        protected $default_print        = '!';
        protected $default_whitespace   = ' ';

        protected $type_delimiter       = ',';
        protected $filter_delimiter     = ',';

        // turn on or off default values for invalid values
        // if the value is found, a rule exists but the type is invalid
        //   true, then return default value for this identifier
        //   false, then return sanitized value (usually this->default_{type})
        protected $use_defaults_invalid = true;

        // turn on or off default values for undefined values
        // if the value was not found but a rule exists
        //   true, then return default value for this identifier
        //   false, then return sanitized value (usually this->default_{type})
        protected $use_defaults_undefined = true;

        // array containing custom rules.
        // array[$identifier] = (bitfield $type, $default);
        protected $rules = array();

        // array containing triggered filters.
        // array[$identifier] = array(array($filter1_id, $params),
        //                            array($filter2_id), ...);
        protected $filters = array();

        // array containing custom filters.
        // array[$name] = $callback
        protected $custom_filters = array();

        // list of contexts (arrays) to search variables in
        protected $contexts = array();

        // singleton
        private static $_instance;

        // name for more debugging information
        public $name;

        // Notifications instance
        public $log;

        //
        // Constructor.
        //
        // The following keys can be provided:
        //   {use_defaults_invalid, use_defaults_undefined, name, log, contexts}
        // log defaults to a new Notifications instance.
        // contexts defaults to a set of superglobals.
        // I will not store any values of unknown keys.
        //
        // @param array options  An associative array for configuration
        //
        public function __construct($options=NULL)
        {
            if (isset($options['use_defaults_invalid']))
                $this->setUseDefaultsInvalid($options['use_defaults_invalid']);

            $key = 'use_defaults_undefined';
            if (isset($options[$key]))
                $this->setUseDefaultsUndefined($options[$key]);

            if (isset($options['name']))
                $this->setName($options['name']);

            if (isset($options['log']))
                $this->log = $options['log'];
            else
                $this->log = new Notifications();

            if (isset($options['contexts']))
            {
                # if contexts is array make it array of arrays
                $invalid = false;
                foreach ($options['contexts'] as $key => $value)
                {
                    if (!is_array($value))
                        $invalid = true;
                }
                if ($invalid)
                    $this->contexts = array($options['contexts']);
                else
                    $this->contexts = $options['contexts'];
            } else {
                $this->contexts = array(
                    &$_SERVER, &$_POST, &$_GET, &$_FILES,
                    &$_COOKIE, &$_SESSION, &$_ENV
                );
                $this->contexts[] = array('argv' => &$GLOBALS['argv'],
                    'argc' => &$GLOBALS['argc']);
            }

            $whitelist = array('use_defaults_invalid', 'use_defaults_undefined',
                'log', 'name', 'contexts');
            if (!isEmpty($options))
            {
                foreach ($options as $key => $value)
                {
                    if (!in_array($key, $whitelist))
                    {
                        $msg = 'Provided key "%s" was not expected';
                        $this->log->push(sprintf($msg, $key), 2);
                    }
                }
            }
        }

        //
        // Get a singleton instance.
        //
        // @param array options  provide parameters for constructor
        // @return Sanitizor  a possibly new instance of Sanitizor
        //
        static public function getInstance($options=NULL)
        {
            if (!isset(self::$_instance))
            {
                $class = __CLASS__;
                self::$_instance = new $class($options);
            }
            return self::$_instance;
        }

        //
        // Use default value for invalid values?
        //
        // @param bool use_defaults_invalid  value to set
        // @return Sanitizor  this
        //
        public function setUseDefaultsInvalid($use_defaults_invalid)
        {
            $this->use_defaults_invalid = (bool)$use_defaults_invalid;
            return $this;
        }

        //
        // Return setting for "Use default value for invalid values?"
        //
        // @return bool  setting
        //
        public function getUseDefaultsInvalid()
        {
            return $this->use_defaults_invalid;
        }

        //
        // Use default value for undefined values?
        //
        // @param bool use_defaults_undefined  value to set
        // @return Sanitizor  this
        //
        public function setUseDefaultsUndefined($use_defaults_undefined)
        {
            $this->use_defaults_undefined = (bool)$use_defaults_undefined;
            return $this;
        }

        //
        // Return setting for "Use default value for undefined values?"
        //
        // @return bool  setting
        //
        public function getUseDefaultsUndefined()
        {
            return $this->use_defaults_undefined;
        }

        //
        // Set the name for this Sanitizor.
        // Will be used for debugging purposes.
        //
        // @param string name  the name to set object to
        // @return Sanitizor  this
        //
        public function setName($name)
        {
            $this->name = $name;
            return $this;
        }

        //
        // Get name of the current Sanitizor.
        //
        // @return string  the name of the object
        //
        public function getName()
        {
            return $this->name;
        }

        // Hooks
        //
        // Hooks make it possible to modify data if certain conditions are met.
        // They have the following format:
        //
        //     Input: (mixed identifier, mixed value)
        //     Output: (mixed identifier, boolean|NULL validity, mixed parameter)
        //             or NULL
        //
        // The hook is called with identifier (the identifier requested) and
        // value (the value found in some context or the parameter to be returned
        // if processing was already done). The hook has to return an array with
        // 3 values:
        //   identifier  the new identifier to use instead [mixed]
        //               (don't use it if you are not aware of the interal effects)
        //   validity    [boolean or NULL]
        //               true, if $parameter is a valid value for this identifier
        //               false, if $parameter is not a valid (but sanitized) value
        //               NULL, if $parameter will be trashed
        //   parameter   the sanitized value for this identifier [mixed]
        //
        // This describes the general interface.
        // Some hooks consist only of a subset of parameters / return values.
        // If the hook returns NULL, the normal handling will be done just like the
        // hook has never been called (is equivalent to return value
        // ($identifier, NULL, $parameter).

        //
        // Hook after the value was found in some context,
        // but before its validity gets checked.
        //
        // @param mixed identifier  the identifier requested
        // @param mixed value  context[identifier]
        // @return array|NULL  array($identifier, $validity, $parameter) or NULL
        //
        public function preProcessingHook($identifier, $value)
        {
            return array($identifier, NULL, $value);
        }

        //
        // Hook invoked after applying filters and
        // before returning $value to user.
        //
        // @param mixed identifier  the identifier requested
        // @param mixed value  the value to be returned to the user
        // @return array|NULL  array($validity, $parameter) or NULL
        //
        public function postProcessingHook($identifier, $value)
        {
            return array(NULL, $value);
        }

        //
        // Hook called when $identifier in context failed the type validity test
        //
        // @param mixed identifier  the identifier requested
        // @param mixed value  context[identifier]
        // @return array|NULL  array($identifier, $validity, $parameter) or NULL
        //
        public function invalidValueHook($identifier, $value)
        {
            return array($identifier, NULL, $value);
        }

        //
        // Hook called when $identifier could not be found in any context.
        //
        // @param mixed identifier  the identifier requested
        // @return array|NULL  array($identifier, $validity, $parameter) or NULL
        //
        public function undefinedValueHook($identifier)
        {
            return array($identifier, NULL, NULL);
        }

        //
        // Even though we shall use default values according to the
        // configuration, no default value was provided for this
        // $identifier.
        //
        // @param mixed identifier  the identifier requested
        // @return array|NULL  array($validity, $parameter) or NULL
        //
        public function noDefaultValueHook($identifier)
        {
            //return NULL;
            // ... or ...
            //throw new UndefinedValueException('Default value was not set');
            // ... or ...
            return array(NULL, NULL);
        }

        //
        // Write string representation of Sanitizor instance to stdout
        //
        // @param string format  either 'text' or 'html'
        // @param int indent  the indentation level
        // @return Sanitizor  this
        //
        public function dump($format='text', $indent=0)
        {
            $in = str_repeat(' ', $indent);
            $out = '';

            switch ($format)
            {
            case 'html':
                $out .= $in.'<div class="dump">'."\n";
                $out .= $in."  <h3>Sanitizor dump</h3>\n";
                $out .= $in."  <dl>\n";
                $out .= $in."    <dt>use_defaults_invalid</dt>\n";
                $out .= $in.'    <dd>'.(int)($this->use_defaults_invalid)."</dd>\n";
                $out .= $in."    <dt>use_defaults_undefined</dt>\n";
                $out .= $in.'    <dd>'.(int)($this->use_defaults_undefined)."</dd>\n";
                $out .= $in."    <dt>_instance is in use?</dt>\n";
                $out .= $in.'    <dd>'.(int)(is_object(self::$_instance)).'</dd>'."\n";
                $out .= $in."    <dt>name</dt>\n";
                $out .= $in.'    <dd><pre>'._e(var_export($this->name, true))
                       ."</pre></dd>\n";
                $out .= $in."    <dt>log</dt>\n";
                $out .= $in.'    <dd>'.$this->log->dump('html')."</dd>\n";
                $out .= $in."  </dl>\n";

                // Print rules
                $out .= $in."  <p>Rules defined for:</p>\n".$in."<ul>\n";
                foreach ($this->rules as $identifier => $rule)
                {
                    $out .= $in."    <li>"._e($identifier).": bitmask 0x".
                         sprintf('%x', $rule[0])." with default value ".
                         nl2br(_e(var_export($rule[1], true)));
                    $out .= $in."</li>\n";
                }
                $out .= $in."  </ul>\n";

                // Print filters
                $out .= $in."  <p>Filters defined for:</p>\n".$in."<ul>\n";
                foreach ($this->filters as $identifier => $filters)
                {
                    $out .= $in."    <li>"._e($identifier).": <ul>\n";
                    foreach ($filters as $filter)
                    {
                        $out .= $in."      <li>Id: "._e($filter[0])
                            ." with parameters ".
                              nl2br(_e(var_export($filter[1], true)))
                            ."</li>\n";
                    }
                    $out .= $in."    </ul></li>\n";
                }
                $out .= $in."  </ul>\n";

                // Custom filters
                $cfilters = count($this->custom_filters);
                if ($cfilters > 0)
                {
                    $out .= $in."  <p>There are ".count($this->custom_filters).
                         " custom filters:</p>\n".$in."  <ul>\n";
                    foreach ($this->custom_filters as $name => $callback)
                    {
                        $out .= $in."    <li>"._e($name)."</li>\n";
                    }
                    $out .= $in."  </ul>\n";
                } else {
                    $out .= $in.'  <p>There are no custom filters.</p>'."\n";
                }

                $out .= $in."  <p>The following contexts are available:</p>\n";
                foreach ($this->contexts as $context)
                {
                    $out .= $in."  <pre>"._e(var_export($context, true))."</pre>\n";
                }
                $out .= $in.'</div>'."\n";
                break;

            default:
            case 'text':
                $out .= $in."Sanitizor dump\n";
                $out .= $in."==============\n\n";
                $out .= $in.'use_defaults_invalid: '.(int)($this->use_defaults_invalid)."\n";
                $out .= $in.'use_defaults_undefined: '.(int)($this->use_defaults_undefined)."\n";
                $out .= $in.'_instance is in use? '.(int)(is_object(self::$_instance))."\n";
                $out .= $in.'name: '.var_export($this->name, true)."\n";
                $out .= $in.'log: '."\n";
                $out .= $in.$this->log->dump('text', $indent+4);
                $out .= "\n\n";

                // Print rules
                $out .= $in."Rules\n";
                $out .= $in."-----\n\n";
                foreach ($this->rules as $identifier => $rule)
                {
                    $out .= $in.'* '.$identifier.': bitmask 0x'
                        .sprintf('%x', $rule[0]).' with default value '
                        .var_export($rule[1], true)."\n";
                }

                // Print filters
                $out .= $in."Filters\n";
                $out .= $in."-------\n\n";
                foreach ($this->filters as $identifier => $filters)
                {
                    $out .= $in.'* '.$identifier.':'."\n";
                    foreach ($filters as $filter)
                    {
                        $out .= $in.'  * Id: '.$filter[0].' with parameters '.
                             var_export($filter[1], true)."\n";
                    }
                }

                // Custom filters
                $cfilters = count($this->custom_filters);
                if ($cfilters > 0)
                {
                    $out .= $in.'There are '.count($this->custom_filters).
                        ' custom filters:'."\n";
                    foreach ($this->custom_filters as $name => $callback)
                    {
                        $out .= $in.'* '.$name."\n";
                    }
                } else {
                    $out .= $in.'There are no custom filters.'."\n";
                }

                $out .= "\n".$in."Contexts\n";
                $out .= $in."--------\n\n";
                foreach ($this->contexts as $id => $context)
                {
                    $out .= $in.'Context #'.$id.': '.var_export($context, true)."\n";
                }
                break;
            }
            return $out;
        }

        //
        // Magic method. String representation.
        //
        public function __toString()
        {
            return $this->dump('text', 0);
        }

        //
        // Add a context. Either store it with an identifier or by numerical
        // index (can be configured by id parameter).
        //
        // @param array context  an associative array to search values in
        // @param mixed id  the associated id (set randomly, but unique)
        // @param bool overwrite  if context exists, overwrite it?
        // @return Sanitizor  $this
        //
        public function addContext($context, $id=NULL, $overwrite=true)
        {
            $id_given = (func_num_args() > 1); // || !is_null($id));
            if ($id_given && array_key_exists($id, $this->contexts))
            {
                if ($overwrite)
                    $this->contexts[$id] = $context;
            } else
                if (!$id_given)
                    $this->contexts[] = $context;
                else
                    $this->contexts[$id] = $context;
            return $this;
        }

        //
        // Remove a context by id.
        //
        // @param mixed id  the id to identify the context
        // @return Sanitizor  this
        //
        public function removeContext($id)
        {
            unset($this->contexts[$id]);
            return $this;
        }

        //
        // Clear all contexts.
        //
        // @return Sanitizor  this
        //
        public function clearContexts()
        {
            $this->contexts = array();
            return $this;
        }

        //
        // Add a new filter.
        //
        // @param identifier mixed  the parameter
        // @param string|int spec  specifies which filters to use
        // @param array|NULL parameters  parameters to be supplied whenever
        //                               the filter is called
        // @param bool overwrite  if filter exists, overwrite it?
        // @return Sanitizor  this
        //
        public function addFilter($identifier, $spec, $parameters=NULL, $overwrite=true)
        {
            $filters = array();
            $num = 0;
            $apply_filters = $this->processFilter($spec);
            $no_parameters = (func_num_args() <= 2);

            // Determine parameter per filter
            if (!$no_parameters)
            {
                if (is_array($parameters) &&
                    count($parameters) === count($apply_filters))
                    $params = $parameters; // only value for each $filter
                else {
                    $params = array();
                    foreach ($apply_filters as $filter)
                    {
                        $params[] = $parameters;
                    }
                }
                $parameters = $params;
            }

            // for each filter given in spec
            foreach ($apply_filters as $num => $filter)
            {
                if (is_array($parameters) &&
                    count($parameters) === count($apply_filters))
                    $parameter = $parameters[$num++];
                else
                    $parameter = $parameters;

                if ($no_parameters)
                    $filters[] = array($filter);
                else
                    $filters[] = array($filter, $parameter);
            }

            $this->filters[$identifier] = $filters;

            return $this;
        }

        //
        // Register a new custom filter to be used.
        // Note. The callback/filter must have the following signature:
        //           ($value, $parameters=NULL) to array(is_valid, filtered_value)
        //
        // @param string name   the name for the custom filter
        // @param callback callback  a callback representing the custom filter
        // @param bool overwrite  if filter exists, overwrite it?
        // @return Sanitizor  this
        //
        public function registerFilter($name, $callback, $overwrite=true)
        {
            $blacklist = array('lower', 'upper', 'between', 'in', 'member',
                'maxlength', 'trim', 'titlecase', 'camelcase');
            if (in_array($name, $blacklist))
            {
                $this->log->push('Registering custom filter '.$name.' already '
                    .'exists. Was not overwritten', Notifications::WARN);
                return $this;
            }

            if (array_key_exists($name, $this->custom_filters))
            {
                if ($overwrite)
                    $this->custom_filters[$name] = $callback;
            } else {
                $this->custom_filters[$name] = $callback;
            }

            return $this;
        }

        //
        // Remove all filters of the value $identifier.
        //
        // @param string identifier  the identifier to modify filters of
        // @return Sanitizor  this
        // 
        public function removeFilters($identifier)
        {
            unset($this->filters[$identifier]);
            unset($this->custom_filters[$identifier]);
            return $this;
        }

        //
        // Clear all filters.
        //
        // @return Sanitizor  this
        //
        public function clearFilters()
        {
            $this->filters = array();
            $this->custom_filters = array();
            return $this;
        }

        //
        // Add a rule.
        //
        // @param string identifier  the parameter identifier to read and parse
        // @param string|int types  the type(s) the parameter must have
        //                          can either be bitfield or comma-separated
        //                          list of types
        // @param mixed default  default value to return if type does not
        //                       match and use_defaults=true.
        // @param bool overwrite  shall I overwrite previously defined
        //                        rules with same name?
        // @return bool  boolean indicating success or failure
        //
        public function addRule($name, $types=NULL,
                                $default=NULL, $overwrite=true)
        {
            if (array_key_exists($name, $this->rules) && !$overwrite)
                return $this;

            if (!is_int($types))
                $types = $this->processTypesStringlist($types,
                    $this->type_delimiter);
            if (func_num_args() <= 2)
                $this->rules[$name] = array($types);
            else
                $this->rules[$name] = array($types, $default);

            return $this;
        }

        //
        // Remove a rule.
        //
        // @param mixed identifier  the identifier to remove rule for
        // @return Sanitizor  this
        //
        public function removeRule($identifier)
        {
            unset($this->rules[$identifier]);
            return $this;
        }

        //
        // Clear all rules.
        //
        // @return Sanitizor  this
        //
        public function clearRules()
        {
            $this->rules = array();
            return $this;
        }

        //
        // Handle values of type NULL.
        //
        // @param mixed value  a value to sanitize
        // @return array  array(is_valid, sanitized)
        //
        protected function handleNull($value)
        {
            if (is_null($value) || strtolower($value) === 'null')
                return array(true, NULL);
            else
                return array(false, $this->default_null);
        }

        //
        // Handle values of type integer.
        //
        // @param mixed value  a value to sanitize
        // @return array  array(is_valid, sanitized)
        //
        protected function handleInteger($value)
        {
            $regex = '/^((0x([[:xdigit:]]+))|(0b([0-1]+))|([[:digit:]]+))$/';

            if (is_int($value))
                return array(true, $value);
            elseif (is_string($value))
            {
                $matches = array();
                preg_match($regex, $value, $matches);
                if (count($matches) > 0)
                    $check = true;
                else
                    $check = false;
            } else
                $check = false;

            if ($check)
            {
                if (strlen($matches[1]) > 0)
                {
                    if (!isEmpty($matches[2]))
                        $val = (int)base_convert($matches[3], 16, 10);
                    else if (!isEmpty($matches[4]))
                        $val = (int)base_convert($matches[5], 2, 10);
                    else if (!isEmpty($matches[6]))
                        $val = (int)$matches[6];
                    else
                        return array(false, $this->default_integer);
                    return array(true, $val);
                } else
                    return array(true, (int)$value);
            } else
                return array(false, $this->default_integer);
        }

        //
        // Handle values of type hex.
        //
        // @param mixed value  a value to sanitize
        // @return array  array(is_valid, sanitized)
        //
        protected function handleHex($value)
        {
            $matches = array();
            if (is_int($value))
                return array(true, $value);
            if (preg_match('/^(0x)?([[:xdigit:]]+)$/', $value, $matches))
                return array(true, hexdec($matches[2]));
            else
                return array(false, $this->default_hex);
        }

        //
        // Handle values of type string.
        //
        // @param mixed value  a value to sanitize
        // @return array  array(is_valid, sanitized)
        //
        protected function handleString($value)
        {
            return array(true, (string)$value);
        }

        //
        // Handle values of type float.
        //
        // @param mixed value  a value to sanitize
        // @return array  array(is_valid, sanitized)
        //
        protected function handleFloat($value)
        {
            if (is_numeric($value))
                return array(true, (float)$value);
            else
                return array(false, $this->default_float);
        }

        //
        // Handle values of type binary.
        //
        // @param mixed value  a value to sanitize
        // @return array  array(is_valid, sanitized)
        //
        protected function handleBinary($value)
        {
            if (is_int($value))
                return array(true, $value);
            else if (is_array($value) || is_object($value))
                return array(false, $this->default_binary);

            $regex = '/^0*([01]+)$/';
            $matches = array();
            preg_match($regex, trim((string)$value), $matches);

            if (count($matches) > 0)
                return array(true, base_convert($matches[1], 2, 10));
            else
                return array(false, $this->default_binary);
        }

        //
        // Handle values of type boolean.
        //
        // @param mixed value  a value to sanitize
        // @return array  array(is_valid, sanitized)
        //
        protected function handleBool($value)
        {
            if (is_bool($value))
                return array(true, $value);
            if (is_array($value) || is_object($value))
                return array(false, $this->default_bool);

            $value = trim((string)$value);

            if (in_array($value, array('0', '1', 'false', 'true')))
                return array(true, in_array($value, array('1', 'true')));
            else
                return array(false, $this->default_bool);
        }

        //
        // Handle values of type loose boolean.
        //
        // @param mixed value  a value to sanitize
        // @return array  array(is_valid, sanitized)
        //
        protected function handleLBool($value)
        {
            if (is_bool($value))
                return array(true, $value);

            $value = trim($value);

            if (empty($value))
                return array(true, false);
            else
                return array(true, true);
        }

        //
        // Handle values of type char.
        //
        // @param mixed value  a value to sanitize
        // @return array  array(is_valid, sanitized)
        //
        protected function handleChar($value)
        {
            $strlen = (function_exists('mb_strlen')) ?
                mb_strlen($value) : strlen($value);
            if ($strlen === 1)
                return array(true, $value);
            else
                return array(false, $this->default_char);
        }

        //
        // Handle values of type alphanumeric.
        //
        // @param mixed value  a value to sanitize
        // @return array  array(is_valid, sanitized)
        //
        protected function handleAlnum($value)
        {
            if (is_int($value))
                return array(true, (string)$value);
            if (is_bool($value))
                return array(true, (string((int)$value)));

            if (preg_match('/^[[:alnum:]]+$/', $value))
                return array(true, $value);
            else
                return array(false, $this->default_alnum);

            return $value;
        }

        //
        // Handle values of type alpha.
        //
        // @param mixed value  a value to sanitize
        // @return array  array(is_valid, sanitized)
        //
        protected function handleAlpha($value)
        {
            if (is_int($value))
                return array(true, (string)$value);
            if (is_bool($value))
                return array(true, (string((int)$value)));

            if (preg_match('/^[[:alpha:]]+$/', $value))
                return array(true, $value);
            else
                return array(false, $this->default_alpha);

            return $value;
        }

        //
        // Handle values of type printables.
        //
        // @param mixed value  a value to sanitize
        // @return array  array(is_valid, sanitized)
        //
        protected function handlePrint($value)
        {
            if (preg_match('/^[[:print:]]+$/', $value))
                return array(true, $value);
            else
                return array(false, $this->default_print);

            return $value;
        }

        //
        // Handle values of type whitespace.
        //
        // @param mixed value  a value to sanitize
        // @return array  array(is_valid, sanitized)
        //
        protected function handleWhitespace($value)
        {
            if (preg_match('/^[[:space:]]+$/', $value) === 1)
                return array(true, $value);
            else
                return array(false, $this->default_whitespace);

            return $value;
        }

        //
        // Apply filter 'Lower'.
        //
        // @param mixed value  the value to filter
        // @param array parameters  parameters to use
        // @return array  array(is_valid, filtered_value)
        //
        protected function filterLower($value, $parameters=NULL)
        {
            if (!is_string($value))
                return array(false, $value);
            return array(true, strtolower($value));
        }

        //
        // Apply filter 'Upper'.
        //
        // @param mixed value  the value to filter
        // @param array parameters  parameters to use
        // @return array  array(is_valid, filtered_value)
        //
        protected function filterUpper($value, $parameters=NULL)
        {
            if (!is_string($value))
                return array(false, $value);
            return array(true, strtoupper($value));
        }

        //
        // Apply filter 'Between'.
        //
        // @param mixed value  the value to filter
        // @param array parameters  parameters to use
        // @return array  array(is_valid, filtered_value)
        //
        protected function filterBetween($value, $parameters=NULL)
        {
            if (!is_numeric($value))
                return array(false, $value);
            if (!($parameters[0] < $value && $value < $parameters[1]))
                return array(false, $value);
            return array(true, $value);
        }

        //
        // Apply filter 'Member'.
        //
        // @param mixed value  the value to filter
        // @param array parameters  parameters to use
        // @return array  array(is_valid, filtered_value)
        //
        protected function filterMember($value, $parameters=NULL)
        {
            if (!in_array($value, $parameters))
                return array(false, $value);
            return array(true, $value);
        }

        //
        // Apply filter 'max length'.
        //
        // @param mixed value  the value to filter
        // @param array parameters  parameters to use
        // @return array  array(is_valid, filtered_value)
        //
        protected function filterMaxLength($value, $parameters=NULL)
        {
            if (!is_string($value))
                return array(false, $value);
            if (!is_null($parameters) && strlen($value) > $parameters)
                return array(true, substr($value, 0, $parameters));
            return array(true, $value);
        }

        //
        // Apply filter 'Trim'.
        //
        // @param mixed value  the value to filter
        // @param array parameters  parameters to use
        // @return array  array(is_valid, filtered_value)
        //
        protected function filterTrim($value, $parameters=NULL)
        {
            if (!is_string($value))
                return array(false, $value);
            return array(true, trim($value));
        }

        //
        // Apply filter 'Title Case'.
        //
        // @param mixed value  the value to filter
        // @param array parameters  parameters to use
        // @return array  array(is_valid, filtered_value)
        //
        protected function filterTitleCase($value, $parameters=NULL)
        {
            if (!is_string($value))
                return array(false, $value);
            return array(true, TitleCase($value));
        }

        //
        // Apply filter 'CamelCase'.
        //
        // @param mixed value  the value to filter
        // @param array parameters  parameters to use
        // @return array  array(is_valid, filtered_value)
        //
        protected function filterCamelCase($value, $parameters=NULL)
        {
            if (!is_string($value))
                return array(false, $value);
            return array(true, camelCase((string)$value));
        }

        //
        // Get filters specification and return array of filter names.
        //
        // @param array|bitfield|string spec  a filter specification
        // @return array  an array of filter names.
        //
        public function processFilter($spec)
        {
            if (is_array($spec))
            {
                $valid = true;
                foreach ($spec as $filter_name)
                {
                    if (!is_string($filter_name))
                    {
                        $msg = 'Filter name is not a string: %s';
                        $this->log->push(sprintf($msg,
                                var_export($filter_name, true)),
                                Notifications::ERROR);
                        $valid = false;
                    }
                }
                if ($valid)
                    return $spec;
                else
                    return false;
            }

            else if (is_int($spec))
            {
                $filters = array();
                $class = __CLASS__;
                $split = $class::_splitBitfield($spec);
                foreach ($split as $bitmask)
                {
                    $filters[] = $this->constantToFilterName($bitmask);
                }

                return $filters;
            }

            else if (is_string($spec))
            {
                return explode($this->filter_delimiter, $spec);
            }
        }

        //
        // Take some filter constant and return its corresponding name.
        //
        // @param int bitvalue  an integer with one bit set
        // @return string  the name of the corresponding filter
        //
        protected function constantToFilterName($bitvalue)
        {
            $class = __CLASS__;
            switch ($bitvalue)
            {
            case $class::FILTER_LOWER:
                return 'lower';
            case $class::FILTER_UPPER:
                return 'upper';
            case $class::FILTER_BETWEEN:
                return 'between';
            case $class::FILTER_MEMBER:
                return 'member';
            case $class::FILTER_MAXLENGTH:
                return 'maxlength';
            case $class::FILTER_TRIM:
                return 'trim';
            case $class::FILTER_TITLECASE:
                return 'titlecase';
            case $class::FILTER_CAMELCASE:
                return 'camelcase';
            default:
                $this->log->push(sprintf('Lookup unknown filter constant %x',
                    $bitvalue), Notifications::WARN);
                return '';
            }
        }

        //
        // Apply filters to the `parameter` using the filters for `identifier`.
        //
        // @param mixed parameter  the parameter evaluated so far
        // @param mixed identifier  the identifier to find filters to apply
        // @return array  an array(validity, new (filtered) parameter)
        //
        protected function applyFilters($parameter, $identifier)
        {
            if (!array_key_exists($identifier, $this->filters) &&
                !array_key_exists($identifier, $this->custom_filters))
                return array(true, $parameter);

            $all_valid = true;
            if (array_key_exists($identifier, $this->filters))
            {
                foreach ($this->filters[$identifier] as $f)
                {
                    $filter_name = $f[0];
                    $clbk = $this->filterNameToCallback($filter_name);
                    if (count($f) >= 2)
                        list($valid, $parameter)
                            = call_user_func($clbk, $parameter, $f[1]);
                    else
                        list($valid, $parameter)
                            = call_user_func($clbk, $parameter);

                    if (!$valid)
                    {
                        $all_valid = false;
                        $this->log->push('Filter '.$filter_name.' returned '
                            .'false validity value for value '
                            .var_export($parameter, true), Notifications::WARN);
                    }
                }
            }
            return array($all_valid, $parameter);
        }

        //
        // Take some filter name and return a corresponding callback.
        //
        // @param string filter_name  a filter name
        // @return callback|NULL  a callback or NULL on error
        //
        protected function filterNameToCallback($filter_name)
        {
            $filter_name = strtolower($filter_name);
            $filters = array(
                'lower'         => 'filterLower',
                'upper'         => 'filterUpper',
                'between'       => 'filterBetween',
                'in'            => 'filterMember',
                'member'        => 'filterMember',
                'maxlength'     => 'filterMaxLength',
                'trim'          => 'filterTrim',
                'titlecase'     => 'filterTitleCase',
                'camelcase'     => 'filterCamelCase'
            );

            if (in_array($filter_name, array_keys($filters)))
                return array($this, $filters[$filter_name]);

            if (array_key_exists($filter_name, $this->custom_filters))
                return $this->custom_filters[$filter_name];

            if (!is_callable($filter_name))
                $this->log->push('Filter '.$filter_name.' does not seem to '
                    .'be callable', Notifications::WARN);

            return $filter_name;
        }

        //
        // Process a list of types given as string.
        //
        // @param string flags  a string defining one or more types
        // @param string delimiter  the delimiter separating types
        // @return int  the corresponding flag constant
        //
        protected function processTypesStringlist($flags, $delimiter=',')
        {
            $flags = explode($delimiter, $flags);
            $flags = array_map('trim', $flags);
            $flags_spec = 0;

            foreach ($flags as $flag)
            {
                $flags_spec |= $this->processType($flag);
            }
            return $flags_spec;
        }

        //
        // Return each bit of the given bitfield separately.
        // Example::
        //
        //     php > _splitBitfield(0x110)
        //     array(0x100, 0x10)
        //
        // @param int bitfield  the bitfield to read
        // @return array  array with integers
        //
        static protected function _splitBitfield($bitfield)
        {
            $split = array();
            $base = 1;

            while ($bitfield !== 0)
            {
                $bitfield >>= 1;
                if ($bitfield & 1)
                    $split[] = pow(2, $base);
                $base++;
            }

            return $split;
        }

        //
        // Process type parameter.
        // Note. Defines a one-directional type-name association.
        //
        // @param int|string type  a type identifier
        // @return int|mixed  the corresponding type constant
        //                    or return value of invalidType on error
        //
        protected function processType($type)
        {
            if (is_int($type))
                return $type;

            $type = strtolower($type);
            switch ($type)
            {
                case 'null':
                    return self::TYPE_NULL;
                case 'int': case 'integer': case 'digits':
                    return self::TYPE_INTEGER;
                case 'hex': case 'hexdec': case 'hexadecimal':
                    return self::TYPE_HEX;
                case 'str': case 'string':
                    return self::TYPE_STRING;
                case 'float':
                    return self::TYPE_FLOAT;
                case 'bin':
                    return self::TYPE_BINARY;
                case 'bool':
                    return self::TYPE_BOOL;
                case 'lbool':
                    return self::TYPE_LOOSE_BOOL;
                case 'char':
                    return self::TYPE_CHAR;
                case 'alnum':
                    return self::TYPE_ALNUM;
                case 'alpha':
                    return self::TYPE_ALPHA;
                case 'print':
                    return self::TYPE_PRINT;
                case 'ws': case 'white': case 'whitespace':
                    return self::TYPE_WHITESPACE;
                default:
                    return $this->invalidTypeGiven($type);
            }
        }

        //
        // Sanitize value according to given types bitmask.
        //
        // @param mixed parameter  the value given by context[identifier]
        // @param mixed identifier  the identifier to work with
        // @return array  an array(is_valid, sanitized_value)
        //                where is_valid says whether or not is in one of types
        //
        protected function sanitizeTypes($parameter, $identifier)
        {
            $rule = $this->rules[$identifier];
            $types = $rule[0];

            $assoc = array(
                self::TYPE_NULL         => 'handleNull',
                self::TYPE_INTEGER      => 'handleInteger',
                self::TYPE_HEX          => 'handleHex',
                self::TYPE_STRING       => 'handleString',
                self::TYPE_FLOAT        => 'handleFloat',
                self::TYPE_BINARY       => 'handleBinary',
                self::TYPE_BOOL         => 'handleBool',
                self::TYPE_LOOSE_BOOL   => 'handleLBool',
                self::TYPE_CHAR         => 'handleChar',
                self::TYPE_ALNUM        => 'handleAlnum',
                self::TYPE_ALPHA        => 'handleAlpha',
                self::TYPE_PRINT        => 'handlePrint',
                self::TYPE_WHITESPACE   => 'handleWhitespace'
            );

            $validate = NULL;
            foreach ($assoc as $bit => $method)
            {
                if (($types & $bit) !== 0)
                {
                    $validate = $this->{$method}($parameter);
                    if ($validate[0])
                        break;
                    // else search for better choices
                }
            }
            if (!is_null($validate))
                return $validate;
            else
                // no type is specified. Then assume it is correct anyway.
                return array(true, $parameter);
        }

        protected function handleIdentifierExists($parameter, $identifier)
        {
            // does a rule for this identifier exist?
            //   if yes then check validity of value
            //     if yes then apply filters and return filtered (validity, value)
            //     if no then use_defaults_invalid?
            //       if yes then default value for this rule is given?
            //         if yes then return (false, rule default value)
            //         if no then return (false, sanitized value)
            //       if no then return (false, sanitized value)
            //   if no then (filtered_validity, filtered context[identifier])

            $rule_exists = array_key_exists($identifier, $this->rules);

            if (!$rule_exists)
            {
                list($validity, $parameter)
                    = $this->applyFilters($parameter, $identifier);

                // postprocessing hook
                $res = $this->postProcessingHook($identifier, $parameter);
                if (!is_null($res))
                {
                    list($valid, $param) = $res;
                    if (!is_null($valid))
                        return array($valid, $param);
                }

                return array($validity, $parameter);
            }

            list($validity, $sanitized_value)
                = $this->sanitizeTypes($parameter, $identifier);

            if ($validity)
            {
                $parameter = $sanitized_value;
                list($validity, $parameter)
                    = $this->applyFilters($parameter, $identifier);

                // postprocessing hook
                $res = $this->postProcessingHook($identifier, $parameter);
                if (!is_null($res))
                {
                    list($valid, $param) = $res;
                    if (!is_null($valid))
                        return array($valid, $param);
                }

                return array($validity, $parameter);

            } else {

                // invalid value hook
                $res = $this->invalidValueHook($identifier, $parameter);
                if (!is_null($res))
                {
                    list($identifier, $validity, $parameter) = $res;
                    if (!is_null($validity))
                        return array($validity, $parameter);
                }

                if ($this->use_defaults_invalid)
                {
                    if (array_key_exists($identifier, $this->rules) &&
                        array_key_exists(1, $this->rules[$identifier]))
                    {
                        return array(false, $this->rules[$identifier][1]);
                    } else {
                        // no default value hook
                        $res = $this->noDefaultValueHook($identifier);
                        if (!is_null($res))
                        {
                            list($validity, $parameter) = $res;
                            if (!is_null($validity))
                                return array($validity, $parameter);
                        }

                        // throw new UndefinedValueException('Default value was not set');
                        return array(false, $sanitized_value);
                    }

                } else {
                    return array(false, $sanitized_value);
                }
            }
        }

        protected function handleIdentifierDoesNotExist($identifier)
        {
            // use_defaults_undefined?
            //   if no then return (false, sanitized value)
            //   if yes then default value for this rule is given?
            //     if no then return (false, NULL)
            //     if yes then return (false, rule default value)

            list($validity, $sanitized_value) = $this->sanitizeTypes(NULL, $identifier);
            $validity = false;  // Is always false.

            if ($this->use_defaults_undefined)
            {
                if (array_key_exists(1, $this->rules[$identifier]))
                {
                    return array(true, $this->rules[$identifier][1]);

                } else {
                    $res = $this->noDefaultValueHook($identifier);
                    if (!is_null($res))
                    {
                        list($validity, $parameter) = $res;
                        if (!is_null($validity))
                            return array($validity, $parameter);
                    }

                    // throw new UndefinedValueException('Default value was not set');
                    return array(false, $sanitized_value);
                }
            } else {
                return array(false, $sanitized_value);
            }
        }

        protected function handleRequest($identifier)
        {
            // Is identifier defined in any context?
            //   if yes then
            //     CALL handleIdentifierExists
            //   if no then does a rule for this identifier exist?
            //     if no then return (false, NULL)
            //     if yes then 
            //       CALL handleIdentifierDoesExist

            foreach ($this->contexts as $context)
            {
                if ($context && array_key_exists($identifier, $context))
                {
                    // is defined in any context
                    // preprocessing hook
                    $res = $this->preProcessingHook($identifier,
                        $context[$identifier]);
                    if (!is_null($res))
                    {
                        list($identifier, $validity, $parameter) = $res;
                        if (!is_null($validity))
                            return array($validity, $parameter);
                    }

                    return $this->handleIdentifierExists
                        ($context[$identifier], $identifier);
                }
            }

            // undefined value hook
            $res = $this->undefinedValueHook($identifier);
            if (!is_null($res))
            {
                list($identifier, $validity, $parameter) = $res;
                if (!is_null($validity))
                    return array($validity, $parameter);
            }

            $rule_exists = array_key_exists($identifier, $this->rules);
            if ($rule_exists)
            {
                return $this->handleIdentifierDoesNotExist($identifier);
            } else {
                return array(false, NULL);
            }
        }

        //
        // Find sanitized value (= parameter) in contexts and parse it
        //
        // @param mixed identifier  the identifier to look for in contexts
        // @return mixed  parameter content, default value, default_{type} or NULL
        //
        public function getParameter($identifier)
        {
            $res = $this->handleRequest($identifier);
            return $res[1];
        }

        //
        // Is the value returned for this identifier
        // some value provided by the contexts?
        //
        // @param mixed identifier  the parameter to search for
        // @return bool  if found, return validity of input else false
        //
        public function getValidity($identifier)
        {
            $res = $this->handleRequest($identifier);
            return $res[0];
        }

        //
        // Get an array([bool] is value from context?, [mixed] sanitized value)
        //
        // @param mixed identifier  the parameter to search for
        // @return array  an array(validity, parameter)
        //
        public function get($identifier)
        {
            return $this->handleRequest($identifier);
        }

        //
        // Magic method.
        //
        // @param mixed name  method name or the identifier to search for
        //                    to test only validity prefix $name with 'v_'
        // @return mixed  parameter content, default value, whatever, ...
        //
        public function __get($name)
        {
            if (property_exists($this, $name))
                return $this->{$name};
            else if (substr($name, 0, 2) === 'v_')
                return $this->getValidity(substr($name, 2));
            else
                return $this->getParameter($name);
        }
    }
?>
