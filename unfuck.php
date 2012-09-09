<?php
    declare(encoding='utf-8');

    // @config  adjust to your own needs
    setlocale(LC_TIME, "en_US.UTF-8");
    date_default_timezone_set("America/New_York");

    
    // E_ERROR, E_WARNING,  E_PARSE, E_NOTICE, E_CORE_ERROR,
    // E_CORE_WARNING, E_COMPILE_ERROR, E_COMPILE_WARNING,
    // E_USER_ERROR, E_USER_WARNING, E_USER_NOTICE, E_STRICT
    // E_RECOVERABLE_ERROR, E_DEPRECATED, E_USER_DEPRECATED
    // E_ALL
    error_reporting(E_ALL | E_STRICT);
    ini_set('display_errors', true);

    // The following source code disables magic_quotes manually.
    //
    // Please be aware, that mysql_real_escape_string (as an
    // exceptional function) will stripslash again. Therefore
    // arguments for mysql_real_escape_string must be addslashed
    // if magic_quotes_gpc=true.
    // Nobody wants that, therefore this source code is disabled!

    /*$__r = &$_REQUEST;
    $__g = &$_GET;
    $__p = &$_POST;
    if (get_magic_quotes_gpc() === 1)
        foreach (array($__r, $__g, $__p) as $__val1)
            foreach ($__val1 as $__key2 => $__val2)
                $__val1[$__key2] = stripslashes($__val2);

    if (get_magic_quotes_gpc()) {
        $_ = array(&$_GET, &$_POST, &$_COOKIE, &$_REQUEST);
        while (list($_k, $_v) = each($_)) {
            foreach ($_v as $k => $v) {
                unset($_[$_k][$k]);
                if (is_array($v)) {
                    $_[$_k][stripslashes($k)] = $v;
                    $_[] = &$_[$_k][stripslashes($k)];
                } else {
                    $_[$_k][stripslashes($k)] = stripslashes($v);
                }
            }
        }
        unset($_);
    }*/

    //
    // A short alias to escape HTML consistently.
    //
    // @param string text  the text to escape
    // @return string  the escaped string
    //
    function _e($text)
    {
        return htmlspecialchars($text, ENT_COMPAT);
    }

    //
    // Capitalize words in the given string
    // (ucwords() but also look for hyphens).
    //
    // @param string string     the words
    // @return string           the capitalized input string
    //
    function capitalize($string)
    {
        $index = 0;
        while ($index !== false)
        {
            if ($string[$index] === '-')
                $string[$index+1] = strtoupper($string[$index+1]);
            $index = strpos($string, '-', $index+1);
        }
        return ucwords($string);
    }

    //
    // CamelCase words in the given string
    //
    // @param string string     the words
    // @return string           the camelcased input string
    //
    function camelCase($string)
    {
        $string = capitalize($string);
        $string = str_replace('-', '', $string);
        $string = preg_replace('/(\w)\.(\s)/', '\1\2', $string);
        $string = preg_replace('/\s+/', '', $string);

        return $string;
    }

    //
    // Check whether parameter starts with a given substring or not.
    //
    // @param string string parameter to search in
    // @param string substring needle to search for
    // @return boolean string starts with substring
    //
    function startswith($string, $substring)
    {
        return substr($string, 0, strlen($substring)) === $substring;
    }

    //
    // Check whether parameter ends with a given substring or not.
    //
    // @param string string parameter to search in
    // @param string substring needle to search for
    // @return boolean string ends with substring
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
    // @return boolean test value to be considered as "empty" or not
    //
    function is_empty($val)
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
    // Notification class
    //
    // A very simple user-level notifications class
    // It handles (message, class) pairs. Used for error messages.
    //
    // Note. If you dislike it, look for trigger_error() or error_log()
    //
    // Possible "classes":
    //    0
    //      Note.
    //    2
    //      Warning.
    //    3
    //      Error.
    //
    // Interface:
    //
    //     @method push($msg, $class=3)
    //     @method pop()
    //     @method count()
    //     @method reset()
    //     @method filter($min=3, $gt=true)
    //     @method iterate()
    //     @method dump($format=0, $indent=0)
    //
    class Notifications
    {
        protected $msgs = array();

        //
        // Add message with associated class
        //
        // @param string msg      the message to store
        // @param integer class   optional, the class (integer)
        // @return false          always returns false!
        //                        So it can be used directly in return contexts
        //
        public function push($msg, $class=3)
        {
            $this->msgs[] = array($msg, $class);
            return false;
        }

        //
        // Pop last message from list.
        //
        // @return array  the popped value with array(msg, class)
        //
        public function pop()
        {
            return array_pop($this->msgs);
        }

        //
        // Get number of messages on stack.
        //
        // @return integer  the number of messages stored
        //
        public function count()
        {
            return count($this->msgs);
        }

        //
        // Reset message stack.
        //
        public function reset()
        {
            $this->msgs = array();
        }

        //
        // A comparison method for sorting
        // (compares classes [in current implementation])
        //
        // @param array msg1  the first message
        // @param array msg2  the second message
        // @return integer    an integer indicating difference of msg1 and msg2
        //
        static protected function _cmp($msg1, $msg2)
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
        // @param integer min  a minimum value the class has to be
        // @param integer gt   test for class >= min on true, <= on false
        // @return array       an array with array(msg, class) values
        //
        public function filter($min=3, $gt=true)
        {
            $result = array();
            foreach ($this->msgs as $value)
            {
                if (($gt && $value[1] >= $min)
                 || (!$gt && $value[1] <= $min))
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
        protected function translate_classes($classes=NULL)
        {
            if ($classes === NULL)
                $classes = array(0 => 'note', 1 => 'alert',
                    2 => 'warning', 3 => 'error');

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
            usort($this->msgs, array(&$this, '_cmp'));

            return $this->msgs;
        }

        //
        // Dump in HTML or plain text.
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
    //       one-dimensional arrays, you can set the array to be a context.
    // Note. In current implementation, if variable occurs in multiple contexts
    //       the first one in $contexts is taken.
    //
    // Interface:
    //
    //     @method __construct($options=NULL)
    //     @method __get($name)
    //     @method getInstance($options=NULL)
    //
    //     @method setUseDefaults($use_defaults)
    //     @method getUseDefaults()
    //     @method setUndefinedValue($undefined_value)
    //     @method getUndefinedValue()
    //     @method setUndefinedDefault($undefined_default)
    //     @method getUndefinedDefault()
    //     @method clearContexts()
    //     @method clearRules()
    //     @method clearFilters()
    //
    //     @method addContext($array, $id=NULL, $overwrite=true)
    //     @method removeContext($name)
    //     @method addRule($name, $types=NULL, $default=NULL, $overwrite=true)
    //     @method addFilter($filter, $parameters=NULL)
    //     @method getValidity($name)
    //     @method getParameter($name)
    //
    class Sanitizor
    {
        const FILTER_LOWER              = 1;
        const FILTER_UPPER              = 2;
        const FILTER_BETWEEN            = 4;
        const FILTER_MEMBER             = 8;

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
        protected $default_print        = ' ';
        protected $default_whitespace   = ' ';

        // turn on or off default values
        // if value is given, but invalid
        //     and use_defaults=true, $rules[2] is returned
        //     and use_defaults=false, self::DEFAULT_VALUE is returned
        protected $use_defaults = true;

        // returned if value is invalid, use_defaults=true
        // and $rules[2] does not exist
        protected $undefined_default = NULL;

        // returned if value does not exist in any context
        // remove definition if Exception for missing property should be thrown
        #protected $undefined_value = NULL;

        // returned if value is invalid, but no default value shall be used
        const DEFAULT_VALUE = NULL;

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

        // logbook -- Notifications instance
        public $log;

        //
        // Constructor.
        //
        // @param boolean use_defaults shall I use default values?
        //
        public function __construct($options=NULL)
        {
            if (isset($options['use_defaults']))
                $this->setUseDefaults($options['use_defaults']);
            if (isset($options['undefined_value']))
                $this->setUndefinedValue($options['undefined_value']);
            if (isset($options['undefined_default']))
                $this->setUndefinedDefault($options['undefined_default']);
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
        }

        static public function getInstance($options=NULL)
        {
            if (!isset(self::$_instance))
            {
                $class = __CLASS__;
                self::$_instance = new $class($options);
            }
            return self::$_instance;
        }

        public function setUseDefaults($use_defaults)
        {
            $this->use_defaults = (bool)$use_defaults;
            return $this;
        }

        public function getUseDefaults()
        {
            return $this->use_defaults;
        }

        public function setUndefinedValue($undefined_value)
        {
            $this->undefined_value = $undefined_value;
            return $this;
        }

        public function getUndefinedValue()
        {
            if (!isset($this->undefined_value))
                return $this->undefined_value;
            else
                return NULL;
        }

        public function setUndefinedDefault($undefined_default)
        {
            $this->undefined_default = $undefined_default;
            return $this;
        }

        public function getUndefinedDefault()
        {
            return $this->undefined_default;
        }

        public function clearContexts()
        {
            $this->contexts = array();
            return $this;
        }

        public function clearRules()
        {
            $this->rules = array();
            return $this;
        }

        public function clearFilters()
        {
            $this->filters = array();
            return $this;
        }

        //
        // Add a context. Either store it with an identifier or by numerical
        // index (can be configured by id parameter).
        //
        // @param array array  an associative array to search values in
        // @param mixed id  the associated id (set randomly, but unique)
        // @param boolean overwrite  if context exists, overwrite it?
        // @return object $this
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
        // @return object $this
        //
        public function removeContext($name)
        {
            unset($this->contexts[$name]);
            return $this;
        }

        //
        // Return each bit of the given bitfield separately.
        // Example::
        //     >>> _splitBitfield(0x110)
        //     array(0x100, 0x10)
        //
        // @param integer bitfield the bitfield to read
        // @return array array with integers
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
        // @param string name  the parameter name to read and parse
        // @param string|integer types
        //        the type(s) the parameter must have
        //        can either be bitfield or comma-separated list of types
        // @param mixed default  default value to return if type does not
        //                       match and use_defaults=true.
        // @param boolean overwrite shall I overwrite previously defined
        //                          rules with same name?
        // @return boolean boolean indicating success (true) or failure (false)
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
        // Add a new filter.
        //
        // @param name mixed  the parameter 
        // @param string|integer filter  a filter specifier
        // @param array|NULL parameters
        //        parameters to be supplied whenever the filter is called
        // @return object $this
        //
        public function addFilter($filter, $parameters=NULL)
        {
            $filter = $this->processFilter($filter);
            if (func_num_args() === 1)
                $this->filters[] = array($filter);
            else
                $this->filters[] = array($filter, $parameters);

            return $this;
        }

        //
        // Process a list of types given as string.
        //
        // @param string types  types specifier
        // @param string delimiter  the delimiter used
        // @return integer the corresponding flag constant
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
        // @param int|string type a type identifier
        // @return integer|mixed the corresponding type constant
        //                       or return value of invalidType on error
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
                    return $this->invalidType($type);
            }
        }

        //
        // Will be called whenever an invalid type is given.
        // Eg. can be used as a hook to write to a logfile
        //
        // @param string type the type identifier name
        // @param boolean returns always false
        //
        protected function invalidType($type)
        {
            $this->log->push('Invalid type given: '.print_r($type, true), 3);
            return 0;
        }

        //
        // Process filter parameter.
        //
        // @param int|string filter a filter identifier
        // @return integer|false the corresponding filter constant
        //                       or false if filter is unknown
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
        // Will be called whenever an invalid filter is given.
        // Eg. can be used as a hook to write to a logfile
        //
        // @param string filter the filter identifier name
        // @param boolean returns always false
        //
        protected function invalid_filter($filter)
        {
            $this->log->push('Invalid filter given: '.print_r($type, true), 3);
            return 0;
        }

        //
        // Handle values of type NULL.
        //
        // @param mixed value a value to sanitize
        // @return array array(is_valid, sanitized)
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
        // @param mixed value a value to sanitize
        // @return array array(is_valid, sanitized)
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
        // @param mixed value a value to sanitize
        // @return array array(is_valid, sanitized)
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
        // @param mixed value a value to sanitize
        // @return array array(is_valid, sanitized)
        //
        protected function handleString($value)
        {
            return array(true, (string)$value);
        }

        //
        // Handle values of type float.
        //
        // @param mixed value a value to sanitize
        // @return array array(is_valid, sanitized)
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
        // @param mixed value a value to sanitize
        // @return array array(is_valid, sanitized)
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
        // @param mixed value a value to sanitize
        // @return array array(is_valid, sanitized)
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
        // @param mixed value a value to sanitize
        // @return array array(is_valid, sanitized)
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
        // @param mixed value a value to sanitize
        // @return array array(is_valid, sanitized)
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
        // @param mixed value a value to sanitize
        // @return array array(is_valid, sanitized)
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
        // @param mixed value a value to sanitize
        // @return array array(is_valid, sanitized)
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
        // @param mixed value a value to sanitize
        // @return array array(is_valid, sanitized)
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
        // @param mixed value a value to sanitize
        // @return array array(is_valid, sanitized)
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
        // @param mixed value       the value to filter
        // @param array parameters  parameters to use
        // @return array array(is_valid, filtered_value)
        //
        protected function filterLower($value, $parameters=NULL)
        {
            if (function_exists('mb_strtolower'))
                return array(true, mb_strtolower($value));
            else
                return array(true, strtolower($value));
        }

        //
        // Apply filter 'Upper'.
        //
        // @param mixed value       the value to filter
        // @param array parameters  parameters to use
        // @return array array(is_valid, filtered_value)
        //
        protected function filterUpper($value, $parameters=NULL)
        {
            if (function_exists('mb_strtoupper'))
                return array(true, mb_strtoupper($value));
            else
                return array(true, strtoupper($value));
        }

        //
        // Apply filter 'Between'.
        //
        // @param mixed value       the value to filter
        // @param array parameters  parameters to use
        // @return array array(is_valid, filtered_value)
        //
        protected function filterBetween($value, $parameters=NULL)
        {
            if (!($parameters[0] < $value && $value < $parameters[1]))
                return array(false, $value);
            return array(true, $value);
        }

        //
        // Apply filter 'Member'.
        //
        // @param mixed value       the value to filter
        // @param array parameters  parameters to use
        // @return array array(is_valid, filtered_value)
        //
        protected function filterMember($value, $parameters=NULL)
        {
            if (!in_array($value, $parameters))
                return array(false, $value);
            return array(true, $value);
        }

        //
        // Sanitize value according to given types bitmask.
        //
        // @param mixed value    the value given by context[searched]
        // @param integer types  a bitmask representing valid types
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
        // @param mixed value    the value given by context[searched]
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
                self::FILTER_MEMBER => 'filterMember'
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
        // @param mixed value    the value given by context[searched]
        // @param integer types  a bitmask representing valid types
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
        // @param string name the parameter to search for
        // @return mixed parameter content, default value, whatever, ...
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
        // @param string name the parameter to search for
        // @return bool if found, return validity of input else false
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
        // Magic method.
        //
        // @param string name the parameter to search for or method name
        // @return mixed parameter content, default value, whatever, ...
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
    }

    //
    // A basic stack implementation.
    // Heavily inspired by python's list datatype.
    //
    // list behavior
    //   A real stack is one where you can only access the top element.
    //   Therefore the interface only provides push() and pop().
    //   However in practice access to other elements is helpful in some
    //   cases. So just like python's list, this datatype methods to
    //   interact with elements "in the middle".
    //
    // stackorder / listorder
    //   If you access elements in the middle of the list. Are those
    //   elements in their natural (order of pushing) or reversed order
    //   (order of popping)? You can configure this in the constructor
    //   and modify the property during runtime (if necessary).
    //   In general you will consider listorder to be more intuitive.
    //
    class Stack {
        const ORDER_LIST = 1;
        const ORDER_STACK = 2;

        protected $max_size;
        protected $counter;
        protected $elements;
        public $order;
        public $name;

        //
        // Constructor.
        //
        // @param int max_size  the maximum size of the stack
        //                      (-1 for infinity)
        // @param boolean order  The order slices of elements are returned
        //                       (either ORDER_STACK or ORDER_LIST)
        //
        public function __construct($max_size=-1,
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
        //
        // @param int size  the size of each chunck
        //
        public function chunk($size)
        {
            if ($this->order == self::ORDER_LIST)
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
        // @return Stack a new Stack instance with elements of diff
        //
        public function diff($stack)
        {
            $new = new Stack($this->max_size, $this->order);
            $diff = array_diff($this->iterate(), $stack->iterate());
            $new->addArray($diff);
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
            return $this->iterate() === $stack->iterate();
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

            $msg = "Provided index for stack is out of range";
            throw OutOfRangeException($msg);
        }

        //
        // Create a new stack with intersected elements between the
        // current stack and the stack provided as argument
        //
        // @param Stack stack  the stack to compare with
        // @return Stack  the stack with intersected elements
        //
        public function intersect($stack)
        {
            $new = new Stack($this->max_size, $this->order);
            $diff = array_intersect($this->iterate(), $stack->iterate());
            $new->addArray($diff);
            return $new;
        }

        //
        // Return an array for iteration according to configured order.
        //
        // @return array  an array you can iterate over
        //
        public function iterate()
        {
            if ($this->order == self::ORDER_LIST)
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
        public function iterate_filtered($callback, $method="callback")
        {
            // Please remember that a callback might be an array
            // (static method). That why $method exists.
            if ($method === "callback")
                return array_diff($callback, $this->iterate());

            return array_filter($this->iterate(), $callback);
        }

        //
        // Apply a callback to each element of the stack. In-place method.
        //
        // @param callback callback  The callback to apply
        //
        public function map($callback)
        {
            return array_map($callback, $this->elements);
        }

        //
        // Array padding.
        // Adjust size of stack to $size by popping elements or
        // pushing (one or more) $value. In-place method.
        //
        // @param int size  the target size
        // @param mixed value  the value to increase size
        //
        public function pad($size, $value)
        {
            $this->elements = array_pad($this->elements, $size, $value);
            $this->counter = $size;
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
        // @return a value (if $count===1) or an array of elements
        //
        public function pop($count=1)
        {
            if ($this->counter < $count)
                throw UnderflowException("Popping too many arguments");

            $elements = array_slice($this->elements, -$count,
                                    $count, false);

            if ($this->order == self::ORDER_STACK)
                $elements = array_reverse($elements, false);

            $this->elements = array_slice($this->elements, 0, -$count);
            $this->counter -= $count;
            return $elements;
        }

        //
        // Add one or more values to the top of the stack.
        // An OverflowException might occur. In-place method.
        //
        // Note. Independent of the configured order, the left-most
        //       value gets pushed first. So the order will be preserved.
        // Note. Use push_rev() for the reversed order.
        // Note. Use pushElement() for the order configured in the
        //       constructor.
        // Note. Variadic function.
        //
        public function push($value)
        {
            if (func_num_args() > 1)
            {
                $args = func_get_args();
                $this->so_check('Failed to push values', count($args));
                array_merge($this->elements, $args);
                $this->counter += count($array);

            } else {
                $this->so_check('Failed to push value', 1);
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
        public function push_rev($value)
        {
            if (func_num_args() > 1)
            {
                $args = array_reverse(func_get_args());
                $this->so_check('Failed to push values', count($args));
                array_merge($this->elements, $args);
                $this->counter += count($array);

            } else {
                $this->so_check('Failed to push value', 1);
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
        public function pushElement()
        {
            if ($this->order == self::ORDER_STACK)
                call_user_func_array(array($this, 'push_rev'),
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

            $result = $this->elements[0];
            $elements = $this->elements;
            if ($this->order == self::ORDER_STACK)
                $elements = array_reverse($elements);

            foreach (array_slice($elements, 1) as $value)
                $result = $callback($result, $value);

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
                throw OutOfRangeException("Replacing element out of range");
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
                throw UnderflowException("Shifting too many arguments");

            $elements = array_slice($this->elements, 0, $count);

            if ($this->order == self::ORDER_STACK)
                $elements = array_reverse($elements, false);

            $this->elements = array_slice($this->elements, $count);
            $this->counter -= $count;
            return $elements;
        }

        //
        // Shuffle stack. In-place method.
        //
        public function shuffle()
        {
            array_values(shuffle($this->elements));
        }

        //
        // Slice the stack.
        //
        // @param int offset  the offset for the slice
        // @param int|null length  length of return value or until end
        // @return array  array of sliced elements
        //
        public function slice($offset, $length=null)
        {
            if ($length === null)
                $elements = array_slice($this->elements, $offset);
            else
                $elements = array_slice($this->elements, $offset, $length);

            if ($this->order == self::ORDER_STACK)
                $elements = array_reverse($elements, false);

            return $elements;
        }

        //
        // Take some slice and replace elements of slice with
        // $replacements. In-place method.
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
                $start = count($this->elements) - $start;
            if ($end < 0)
                $end = count($this->elements) - $end;

            if ($start === $end)
                return $this->replace($start, $replacements[0]);

            if ($start > $end)
                throw RangeException("Cannot splice above Stack bounds");

            $head = array_slice($this->elements, 0, $start);
            $tail = array_slice($this->elements, $end);

            $this->elements = array_merge($head, $replacements, $tail);
        }

        //
        // Sort elements of the stack. In-place method.
        // Note. You might wanna use iterate() or pop() afterwards.
        //
        public function sort()
        {
            sort($this->elements);
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
        protected function so_check($msg, $additionals=0)
        {
            if ($this->max_size == -1)  // infinite size stack
                return;

            $size = $this->counter + (int)$additionals;

            if (is_empty($name))
                $name = '';
            else
                $name = '[Stack '.$this->name.']';

            if ($size > $this->max_size)
            {
                $overflow = '[size %d > %d]';
                $overflow = sprintf($overflow, $size, $this->max_size);

                $error_msg = implode(' ', array($name, $overflow, $msg));
                throw OverflowException($error_msg);

            } elseif (0 > $size) {
                $overflow = '[size 0 > %d]';
                $overflow = sprintf($overflow, $size);

                $error_msg = implode(' ', array($name, $overflow, $msg));
                throw OverflowException($error_msg);
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
        public function unique($sort_flags)
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
            $this->so_check('Cannot unshift so many elements',
                            count($array));

            if ($this->order == self::ORDER_LIST)
                $array = array_reverse($array, false);

            $this->elements = array_merge($array, $this->elements);
        }
    }
?>