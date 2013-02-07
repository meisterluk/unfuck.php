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
    // @return boolean  string starts with substring
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
    // @return boolean  string ends with substring
    //
    function endswith($string, $substring)
    {
        return substr($string, -strlen($substring)) === $substring;
    }

    //
    // A simple empty() replacement.
    // Sorry, but code stands for itself.
    //
    // @param mixed val a value to parse
    // @return boolean  test value to be considered as 'empty' or not
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


    // Custom exceptions

    class UndefinedValueException extends InvalidArgumentException { }
    class LookupException extends LogicException { }
    class EnvironmentException extends RuntimeException { }
    class NotImplementedException extends RuntimeException { }
    class CharsetException extends RuntimeException { }

    //
    // A basic stack implementation.
    // Heavily inspired by python's list datatype.
    //
    // *list behavior*
    //   A real stack is one where you can only access the top element.
    //   Therefore the interface only provides push() and pop().
    //   However in practice access to other elements is helpful in some
    //   cases. So just like python's list, this datatype methods to
    //   interact with elements 'in the middle'.
    //
    // *stackorder / listorder*
    //   If you access elements in the middle of the list. Are those
    //   elements in their natural (order of pushing) or reversed order
    //   (order of popping)? You can configure this in the constructor
    //   and modify the property during runtime (if necessary).
    //   In general you will consider listorder to be more intuitive.
    //
    // TODO:
    //   return $this for public methods where possible
    //
    // TODO:
    //   Probably it's a better idea to provide order as parameter for
    //   each method depending on its configuration. And remove the
    //   global variable to avoid global state.
    //
    // @method __construct($max_size=self::INFINITE_SIZE, $order=self::ORDER_LIST)
    // @method chunk($size)
    // @method clear()
    // @method count()
    // @method diff($stack)
    // @method equals($stack)
    // @method exists($value)
    // @method getName()
    // @method getStackSize()
    // @method index($index)
    // @method intersect($stack)
    // @method iterate()
    // @method iterateFiltered($callback, $method='callback')
    // @method map($callback)
    // @method pad($size, $value)
    // @method pop($count=1)
    // @method push($value)
    // @method pushRev($value)
    // @method pushElement()
    // @method reduce($callback)
    // @method replace($index, $replacement)
    // @method replaceElements($replacements)
    // @method reverse()
    // @method setName($name)
    // @method shift($count=1)
    // @method shuffle()
    // @method slice($offset, $length=null)
    // @method splice($start, $end, $replacements)
    // @method sort()
    // @method unique($sort_flags)
    // @method unshift($array)
    //
    class Stack {
        const ORDER_LIST = 1;
        const ORDER_STACK = 2;
        const INFINITE_SIZE = -1;

        protected $max_size;
        protected $counter;
        protected $elements;
        public $order;
        public $name;

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
        // An interface towards php's array_chunk.
        // Splits an array into equal-sized parts.
        // Order of chunks depend on configured order.
        //
        // @param int size  the size of each chunck
        // @return array  an array containing chunks of the stack
        //
        public function chunk($size)
        {
            if ($this->order === self::ORDER_LIST)
                $elements = $this->elements;
            else
                $elements = array_reverse($this->elements, false);

            return array_chunk($elements, $size, false);
        }

        //
        // Clear the stack. In-place method.
        //
        public function clear()
        {
            $this->elements = array();
            $this->counter = 0;
        }

        //
        // Copy the stack.
        // Create a new Stack instance with the same properties.
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
        // Get a new Stack with set of elements different in this stack
        // and the stack provided as argument.
        // Note. The position of the element is not important.
        //
        // @param Stack stack  a Stack to compare with
        // @return Stack  a new Stack instance with elements of diff
        //
        public function diff($stack)
        {
            $new = new Stack($this->max_size, $this->order);
            $diff = array_diff($this->iterate(), $stack->iterate());
            if (!isEmpty($diff))
                call_user_func_array(array($new, 'push'), $diff);
            return $new;
        }

        //
        // Test equality with another stack.
        //
        // @param Stack stack  a stack to compare with
        // @return boolean  is equal
        //
        public function equals($stack)
        {
            $old_order_this = $this->order;

            $this->order = $stack->order;
            $equal = ($this->iterate() === $stack->iterate());
            $this->order = $old_order_this;

            return $equal;
        }

        //
        // Does $value exist as element in the stack?
        //
        // @param mixed value  the value to look for
        // @return boolean  $value exists in stack?
        //
        public function exists($value)
        {
            return in_array($value, $this->elements);
        }

        //
        // Get the name assigned to this Stack.
        //
        // @return string  the name you assigned to this stack
        //
        public function getName()
        {
            return (string)$this->name;
        }

        //
        // Get the size of the current stack.
        // Ie. the number of elements on the stack
        //
        // @return int  the number of elements
        //
        public function getStackSize()
        {
            return $this->counter;
        }

        //
        // Indexing.
        // Access element at $index or throw OutOfRangeException
        //
        // @param int index  the index you want to access
        //
        public function index($index)
        {
            if (array_key_exists($index, $this->elements))
                return $this->elements[$index];

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
            $new = new Stack($this->max_size, $this->order);
            $cmp_elements = is_array($stack) ? $stack : $stack->iterate();
            $diff = array_intersect($this->iterate(), $cmp_elements);
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
            // Please remember that a callback might be an array
            // (static method). That's why $method exists.
            if ($method === 'callback')
                return array_filter($this->iterate(), $callback);

            return array_values(array_diff($this->iterate(), $callback));
        }

        //
        // Apply a callback to each element of the stack. In-place method.
        //
        // @param callback callback  The callback to apply
        //
        public function map($callback)
        {
            $this->elements = array_map($callback, $this->elements);
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
        //
        public function pad($size, $value)
        {
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
        // @param count  how many elements shall be popped?
        // @return mixed  a value (if $count===1) or an array of elements
        //
        public function pop($count=1)
        {
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
        }

        //
        // Add one or more values to the top of the stack.
        // The order depends on the configured order in the constructor.
        // In-place method.
        //
        // Note. See also push()
        //
        // @param mixed value  the value to push
        //
        public function pushElement($value)
        {
            if ($this->order === self::ORDER_STACK)
                call_user_func_array(array($this, 'pushRev'),
                                     func_get_args());
            else
                call_user_func_array(array($this, 'push'),
                                     func_get_args());
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
        //
        public function replace($index, $replacement)
        {
            if (array_key_exists($index, $this->elements))
                $this->elements[$index] = $replacement;
            else
                throw new OutOfRangeException('Replace element out of range');
        }

        //
        // Replace elements using the associative array $replacements.
        // In-place method.
        //
        // @param array replacements  an associative array with keys already
        //                            pushed at stack to be replaced by the
        //                            corresponding value
        //
        public function replaceElements($replacements)
        {
            foreach ($this->elements as $key => $value)
            {
                if (array_key_exists($value, $replacements))
                    $this->elements[$key] = $replacements[$value];
            }
        }

        //
        // Reverse the stack. In-place method.
        //
        public function reverse()
        {
            $this->elements = array_reverse($this->elements, false);
        }

        //
        // Set a name. Provide better messages in error handling.
        // In-place method.
        //
        // @param string name  a name for the stack
        //
        public function setName($name)
        {
            $this->name = (string)$name;
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
        // @param count  how many elements shall be shifted?
        // @return a value (if $count===1) or an array of elements
        //
        public function shift($count=1)
        {
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
        // @return boolean  boolean indicating success or failure
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

            if ($length === null)
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
        }

        //
        // Sort elements of the stack. In-place method.
        // Note. You might wanna use iterate() or pop() afterwards.
        //
        // @param callback cmp_func  a custom comparison function
        //
        public function sort($cmp_func=NULL)
        {
            if ($cmp_func === NULL)
                sort($this->elements);
            else
                usort($this->elements, $cmp_func);
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
        // Remove duplicate values from stack. In-place method.
        //
        // [0] http://us.php.net/manual/de/function.array-unique.php
        //
        // @param int sort_flags  Flags defining comparison configuration
        //                        see also [0]
        //
        public function unique($sort_flags=SORT_REGULAR)
        {
            $this->elements = array_unique($this->elements, $sort_flags);
            $this->counter = count($this->elements);
        }

        //
        // Will insert $array at the bottom of the stack. In-place method.
        // Note. It will be inserted in the configured order.
        //
        // @array array array  an array to shift
        //
        public function unshift($array)
        {
            $this->SOcheck('Cannot unshift so many elements',
                            count($array));

            if ($this->order === self::ORDER_STACK)
                $array = array_reverse($array, false);

            $this->elements = array_merge($array, $this->elements);
            $this->counter = count($this->elements);
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
        // The magic __clone method (Object cloning).
        //
        // @return Stack  an cloned Stack instance.
        //
        public function __clone()
        {
            $stack = new Stack($this->max_size, $this->order);
            $stack->elements = $this->elements;
            $stack->counter = $this->counter;
            $stack->name = $this->name;

            return $stack;
        }

        //
        // String representation of the stack.
        // Magic method __tostring.
        //
        // @return string  the string representation
        //
        public function __tostring()
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
    // @method dump($format=0, $indent=0)
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
            elseif ($msg[1] === $msg2[1])
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
        // @param classes a map between source classes and translated classes
        //
        protected function translateClasses($classes=NULL)
        {
            if ($classes === NULL)
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
        // @return array of messages
        //
        public function iterate()
        {
            $this->msgs->sort(array($this, '_class_cmp'));

            return $this->msgs->iterate();
        }

        //
        // Dump in HTML or plain text.
        //
        // @param format int  the format {0: HTML, 1: plain text}
        // @param indent int  the level of indentation
        //                    (ie. number of spaces before each line)
        // @return string  a string describing the state of the object
        //
        public function dump($format=0, $indent=0)
        {
            $in = str_repeat(' ', $indent);
            $out = '';
            $this->translateClasses();

            if ($format === 0)
            { // HTML
                $out .= $in.'<div class="dump">'."\n";
                $out .= $in.'  <h1>HTML Dump</h1>'."\n\n";
                $out .= $in.'  <ul>'."\n";
                foreach ($this->msgs as $msg)
                {
                    $out .= $in.'    <li class="'._e($msg[1]).'">'
                            ._e($msg[0]).'</li>'."\n";
                }
                $out .= $in.'  </ul>'."\n";

            } else {
                $out .= "Dump\n";
                $out .= "====\n\n";
                foreach ($this->msgs as $msg)
                {
                    $out .= $in.' * ('.$msg[1].') '.$msg[0]."\n";
                }
            }
            return $out;
        }
    }

    //
    // Sanitizor class.
    // If you dislike this class, you might want to check out the
    // filter_var function of the "Filter Functions" module in PHP5.
    //
    // Note. This is not a pure singleton class. It still allows instantiation,
    //       but a singleton can be accessed by the getInstance() method
    // Note. This class does not support arrays as parameters. To process
    //       one-dimensional arrays, you can set the array to be a context,
    //       but input is supposed to be flat (not hierarchical) here.
    // Note. In current implementation, if variable occurs in multiple contexts
    //       the first $contexts containing it is taken.
    //
    // Hooks
    //   The Sanitizor provides several hooks. These are defined states reached
    //   during the evaluation of a parameter. You might want to inherit this
    //   class and override the hooks with the subclass to your desired behavior.
    //
    //
    // Public API:
    //
    // @method __construct($options=NULL)
    // @method getInstance($options=NULL)
    //
    // @method setUseDefaults($use_defaults)
    // @method getUseDefaults()
    // @method setName($name)
    // @method getName()
    //
    // @method preProcessingHook($identifier, $value)
    // @method postProcessingHook($identifier, $value)
    // @method undefinedValueHook($identifier)
    // @method invalidValueHook($identifier, $value)
    // @method noDefaultValueHook($identifier, $value)
    //
    // @method addContext($array, $id=NULL, $overwrite=true)
    // @method removeContext($identifier)
    // @method clearContexts()
    //
    // @method addFilter($identifier, $filter, $parameters=NULL)
    // @method removeFilter($identifier)
    // @method clearFilters()
    //
    // @method addRule($identifier, $types=NULL, $default=NULL, $overwrite=true)
    // @method getValidity($identifier)
    // @method getParameter($identifier)
    // @method __get($identifier)
    // @method clearRules()
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
        #protected $default_loose_bool   = 0;
        protected $default_char         = '0';
        protected $default_alnum        = '0';
        protected $default_alpha        = 'A';
        protected $default_print        = '!';
        protected $default_whitespace   = ' ';

        // turn on or off default values
        // if value is given, but invalid
        //     and use_defaults=true, the default value is returned
        //     and use_defaults=false, invalidValueHook gets called
        protected $use_defaults = true;

        // array containing rules.
        // array[$identifier] = (bitfield $type, $default);
        protected $rules = array();

        // array containing filters.
        // array[$identifier] = array(array($filter1_id, $params),
        //                            array($filter2_id), ...);
        protected $filters = array();

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
        //   {use_defaults, name, log, contexts}
        // log defaults to a new Notifications instance.
        // contexts defaults to a set of superglobals.
        // Will not write to log if unknown key is given.
        //
        // @param array options  An associative array for configuration
        //
        public function __construct($options=NULL)
        {
            if (isset($options['use_defaults']))
                $this->setUseDefaults($options['use_defaults']);
            else
                $this->setUseDefaults(true);
            if (isset($options['name']))
                $this->setName($options['name']);

            if (isset($options['log']))
                $this->log = $options['log'];
            else
                $this->log = new Notifications();

            if (isset($options['contexts']))
                $this->contexts = $options['contexts'];
            else
                $this->contexts = array(
                    &$_SERVER, &$_REQUEST, &$_FILES,
                    &$_COOKIE, &$_SESSION, &$_ENV
                );

            $whitelist = array('use_defaults', 'log', 'name', 'contexts');
            if (!isEmpty($options))
            {
                foreach ($options as $key => $value)
                {
                    if (array_key_exists($key, $whitelist))
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
        // Use default value?
        //
        // @param bool use_defaults  value to set
        // @return Sanitizor  this
        //
        public function setUseDefaults($use_defaults)
        {
            $this->use_defaults = (bool)$use_defaults;
            return $this;
        }

        //
        // Return setting for "Use default value?"
        //
        // @return bool  setting
        //
        public function getUseDefaults()
        {
            return $this->use_defaults;
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
        // Hook after the value was found in some context,
        // but before its validity gets checked.
        //
        // @param string identifier  the identifier requested
        // @param mixed value  the actual value given in the context
        // @return array  an array($identifier, $value)
        //
        public function preProcessingHook($identifier, $value)
        {
            return array($identifier, $value);
        }

        //
        // Hook invoked after applying filters and
        // before returning $value to user.
        //
        // @param string identifier  the identifier requested
        // @param mixed value  the actual value given in the context
        // @return mixed  $identifier
        //
        public function postProcessingHook($identifier, $value)
        {
            return $identifier;
        }

        //
        // Hook called when $identifier could not be found in any context.
        //
        // @param string identifier  the identifier requested
        // @return mixed  the value returned to the user
        //
        public function undefinedValueHook($identifier)
        {
            $msg = 'Value is undefined in Sanitzor contexts';
            throw new UndefinedValueException($msg);
            return NULL;
        }

        //
        // Hook invoked when value is invalid and defaults shall not
        // be used.
        //
        // @param string identifier  the identifier requested
        // @param mixed value  the value to be returned
        // @return mixed  the value returned to the user
        //
        public function invalidValueHook($identifier, $value)
        {
            $msg = 'Value given for "'.$identifier.'" is invalid';
            throw new UnexpectedValueException($msg);
            return NULL;
        }

        //
        // Even though we shall use default values according to the
        // configuration, no default value was provided for this
        // $identifier.
        //
        // @param string identifier  the identifier requested
        // @param mixed value  the value to be returned
        // @return mixed  the value returned to the user
        //
        public function noDefaultValueHook($identifier, $value)
        {
            //$this->rules[$identifier]
            // TODO
        }

        //
        // A type got specified as parameter which is unknown / invalid.
        // Eg. can be used as a hook to write to a logfile
        //
        // @param string type  the type specified
        //
        protected function invalidTypeGiven($type)
        {
            $this->log->push('Invalid type given: '.print_r($type, true), 3);
            $msg = 'Invalid type specifier "%s"';
            throw new InvalidArgumentException(sprintf($msg, $type));
        }

        //
        // An type got specified as parameter which is unknown / invalid.
        // Eg. can be used as a hook to write to a logfile
        //
        // @param string type  the type specified
        //
        protected function invalidFilter($filter)
        {
            $this->log->push('Invalid filter given: '.print_r($type, true), 3);
            return 0;
        }











/* TODO
        //
        // Add a context. Either store it with an identifier or by numerical
        // index (can be configured by id parameter).
        //
        // @param array array  an associative array to search values in
        // @param mixed id  the associated id (set randomly, but unique)
        // @param boolean overwrite  if context exists, overwrite it?
        // @return Sanitizor  $this
        //
        public function addContext($array, $id=NULL, $overwrite=true)
        {
            if ($id !== NULL && array_key_exists($id, $this->contexts))
            {
                if ($overwrite)
                    $this->contexts[$id] = $array;
            } else
                if ($id === NULL)
                    $this->contexts[] = $array;
                else
                    $this->contexts[$id] = $array;
            return $this;
        }

        //
        // Remove a context by id.
        //
        // @param mixed id  the id to identify the context
        // @return Sanitizor  this
        //
        public function removeContext($name)
        {
            unset($this->contexts[$name]);
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
        // @param string|int filter  a filter specifier
        // @param array|NULL parameters  parameters to be supplied whenever
        //                               the filter is called
        // @return Sanitizor  this
        //
        public function addFilter($identifier, $filter, $parameters=NULL)
        {
            // TODO: evaluate when two filters cannot be applied at the same time
            $filter = $this->processFilter($filter);
            if (func_num_args() === 2)
                $this->filters[$identifier] = array($filter);
            else
                $this->filters[$identifier] = array($filter, $parameters);

            return $this;
        }

        //
        // Remove all filters of the value $identifier.
        //
        // @param string identifier  the identifier to modify filters of
        // @return Sanitizor  this
        // 
        public function removeFilter($identifier)
        {
            unset($this->filters[$identifier]);
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
            return $this;
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
        // Add a rule.
        //
        // @param string identifier  the parameter identifier to read and parse
        // @param string|int types  the type(s) the parameter must have
        //                          can either be bitfield or comma-separated
        //                          list of types
        // @param mixed default  default value to return if type does not
        //                       match and use_defaults=true.
        // @param boolean overwrite  shall I overwrite previously defined
        //                           rules with same name?
        // @return boolean  boolean indicating success or failure
        //
        public function addRule($name, $types=NULL,
                                $default=NULL, $overwrite=true)
        {
            if (array_key_exists($name, $this->rules) && !$overwrite)
                return $this;

            if (!is_int($types))
                $types = $this->processTypesStringlist($types, ',');
            $this->rules[$name] = array($types, $default);

            return $this;
        }

        //
        // Process a list of types given as string.
        //
        // @param string flags  a string several types
        // @param string delimiter  the delimiter used
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
        // Process type parameter.
        // Note. Defines a one-directional type-name association.
        //
        // @param int|string type  a type identifier
        // @return integer|mixed  the corresponding type constant
        //                        or return value of invalidType on error
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
        // Process filter parameter.
        //
        // @param int|string filter  a filter identifier
        // @return integer|false  the corresponding filter constant
        //                        or false if filter is unknown
        //
        protected function processFilter($filter)
        {
            if (is_int($filter))
                return $filter;

            $filter = strtolower($filter);
            switch ($filter)
            {
                case 'lower': case 'strtolower':
                    return self::FILTER_LOWER;
                case 'upper': case 'strtoupper':
                    return self::FILTER_UPPER;
                case 'between': case 'range':
                    return self::FILTER_BETWEEN;
                case 'member': case 'in':
                    return self::FILTER_MEMBER;
                default:
                    return $this->invalidFilter($filter);
            }
        }

        //
        // Handle values of type NULL.
        //
        // @param mixed value  a value to sanitize
        // @return array  array(is_valid, sanitized)
        //
        protected function handleNull($value)
        {
            if ($value === NULL || strtolower($value) === 'null')
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
            if (is_int($value))
                return array(true, $value);
            elseif (function_exists('ctype_digit'))
                $check = ctype_digit($value);
            elseif (preg_match('/^[[:digit:]]+$/', $value))
                $check = true;
            else
                $check = false;

            if ($check)
                return array(true, (int)$value);
            else
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
            if (preg_match('/^[[:xdigit:]]+$/', $value))
                return array(true, hexdec($value));
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
            $value = trim($value);

            if (in_array(array('0', '1'), $value))
                return array(true, $value === '1');
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

            $value = trim($value);

            if (in_array(array('0', '1', 'false', 'true'), $value))
                return array(true, in_array(array('1', 'true'), $value));
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
        protected function handleWhite($value)
        {
            if (preg_match('/^[[:space:]]+$/', $value))
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
            if (strlen($value) > $parameters[0])
                return array(true, substr($value, 0, $parameters[0]));
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
        // Sanitize value according to given types bitmask.
        //
        // @param mixed value  the value given by context[searched]
        // @param int types  a bitmask representing valid types
        // @return array  an array(is_valid, sanitized_value)
        //                where is_valid says whether or not is in one of types
        //
        protected function sanitizeTypes($value, $types)
        {
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
                    $validate = $this->{$method}($value);
                    if ($validate[0])
                        break;
                    // else search for better choices
                }
            }
            if ($validate !== NULL)
                return $validate;
            else
                // no type is specified. Then assume it is correct anyway.
                return array(true, $value);
        }

        //
        // Apply all filters given by $filters to $value.
        //
        // @param mixed value  the value given by context[searched]
        // @param array filters  list of arrays (filter, params) to be applied
        // @return array  an array(is_valid, filtered_value)
        //                where is_valid says whether or not is in one of types
        //
        protected function applyFilters($value, $filters=NULL)
        {
            if (!is_array($filters) || empty($filters))
                return array(true, $value);

            $assoc = array(
                self::FILTER_LOWER => 'filterLower',
                self::FILTER_UPPER => 'filterUpper',
                self::FILTER_BETWEEN => 'filterBetween',
                self::FILTER_MEMBER => 'filterMember',
                self::FILTER_MAXLENGTH => 'filterMaxLength',
                self::FILTER_TRIM => 'filterTrim',
                self::FILTER_TITLECASE => 'filterTitleCase',
                self::FILTER_CAMELCASE => 'filterCamelCase'
            );
            foreach ($filters as $filter)
            {
                foreach ($assoc as $bit => $method)
                {
                    if (($filter[0] & $bit) !== 0)
                    {
                        $validate = $this->{$method}($value, $filter[1]);
                        if ($validate[0] === false)
                            break;
                    }
                }
            }
            return $validate;
        }

        //
        // Parse a parameter value.
        //
        // @param mixed value  the value given by context[searched]
        // @param int types  a bitmask representing valid types
        // @param array filters  list of arrays (filter, params) to be applied
        // @return array  an array(is_valid, sanitized_and_filtered_value)
        //                where is_valid says whether or not is in one of types
        //
        protected function parse($value, $types=0, $filters=NULL)
        {
            // apply type constraints
            $validate = $this->sanitizeTypes($value, $types);

            // apply filters (only if valid value is available)
            if ($validate[0] === true)
                $validate = $this->applyFilters($validate[1], $filters);

            return $validate;
        }

        //
        // Find parameter in contexts and parse it
        //
        // @param string name  the parameter to search for
        // @return mixed  parameter content, default value, whatever, ...
        //
        public function getParameter($name)
        {
            $some_value = NULL;
            foreach ($this->contexts as $context)
            {
                if ($context && array_key_exists($name, $context))
                {
                    if (array_key_exists($name, $this->rules))
                    {
                        if (array_key_exists($name, $this->filters))
                            $result = $this->parse($context[$name],
                                $this->rules[$name][0], $this->filters[$name]);
                        else
                            $result = $this->parse($context[$name],
                                $this->rules[$name][0]);

                        if (!$result[0])
                            if ($this->use_defaults)
                                if (isset($this->rules[$name][1]))
                                    return $this->rules[$name][1];
                                else
                                    return $this->undefined_default;
                            else
                                return self::DEFAULT_VALUE;
                        else
                            return $result[1];
                    } elseif ($some_value === NULL) {
                        // I found some value but no associated rules
                        $some_value = $context[$name];
                    }
                }
            }
            if ($some_value === NULL)
            {
                $exists = true;
                if (!isset($this->undefined_value))
                {
                    $attrs  = array_keys(get_class_vars(get_class($this)));
                    $exists = in_array('undefined_value', $attrs);
                }
                if ($exists)
                    return $this->undefined_value;
                else
                    return $this->{$name};
            } else
                // assume defined value is okay but no rules are given
                return $some_value;
        }

        //
        // Get name from contexts and return validity of a variable.
        //
        // @param string name  the parameter to search for
        // @return boolean  if found, return validity of input else false
        //
        public function getValidity($name)
        {
            foreach ($this->contexts as $context)
            {
                if ($context && array_key_exists($name, $context))
                {
                    if (array_key_exists($name, $this->rules))
                    {
                        if (array_key_exists($name, $this->filters))
                            $result = $this->parse($context[$name],
                                $this->rules[$name][0], $this->filters[$name]);
                        else
                            $result = $this->parse($context[$name],
                                $this->rules[$name][0]);

                        return $result[0];
                    }
                }
            }
            return false;
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
        // Magic method.
        //
        // @param string name  the parameter to search for or method name
        // @return mixed  parameter content, default value, whatever, ...
        //
        public function __get($name)
        {
            if (substr($name, 0, 2) === 's_')
                return $this->getParameter(substr($name, 2));
            elseif (substr($name, 0, 2) === 'v_')
                return $this->getValidity(substr($name, 2));
            else
                return $this->{$name};
        }
*/
    }
?>
