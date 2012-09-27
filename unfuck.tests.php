<?php
    require_once('unfuck.php');

    class TestingAdapter {

        public function run()
        {
            $reflector = new ReflectionClass($this);
            printf('Running tests in %s...', $reflector->getName());
            echo "\n";
            foreach ($reflector->getMethods() as $method) {
                $name = $method->getName();
                if (substr($name, 0, 4) === 'test') {
                    printf("* Run %s\n", $name);
                    $this->{$name}();
                }
            }
            echo 'Done.', "\n";
        }

        public function assertEquals($val1, $val2)
        {
            $msg = 'assertEquals fails with `%s` !== `%s`';
            $args = func_get_args();

            if (count($args) == 2)
            {
                if ($val1 !== $val2)
                    throw new UnexpectedValueException(
                        sprintf($msg, var_export($val1, true),
                                      var_export($val2, true)));
            } else {
                $ref = array_pop($args);
                for ($i=0; $i<count($args)-1; $i++)
                {
                    $ref2 = array_pop($args);
                    if ($ref !== $ref2)
                        throw new UnexpectedValueException(
                            sprintf($msg, var_export($ref, true),
                                          var_export($ref2, true)));
                    $ref = $ref2;
                }
            }
        }

        public function assertTrue($value)
        {
            if ($value === false) {
                throw new UnexpectedValueException('assertTrue is not true');
            } else if ($value === true) {
            } else {
                throw new InvalidArgumentException('Argument of invalid type');
            }
        }

        public function assertFalse($value)
        {
            if ($value === true) {
                throw new UnexpectedValueException('assertFalse is not false');
            } else if ($value === false) {
            } else {
                throw new InvalidArgumentException('Argument of invalid type');
            }
        }
    }

    class FunctionsTesting extends TestingAdapter {

        public function test_e()
        {
            $cmp = array(
                'test' => 'test',
                'abc & def' => 'abc &amp; def',
                'javascript:alert("Hello World")'
                    => 'javascript:alert(&quot;Hello World&quot;)',
                '<xml>' => '&lt;xml&gt;'
            );
            foreach ($cmp as $key => $value)
                $this->assertEquals(_e($key), $value);
        }

        public function testTitlecase()
        {
            $cmp = array(
                'test' => 'Test',
                'Test' => 'Test',
                'TEST' => 'Test',
                'foo bar' => 'Foo Bar',
                'foo-bar' => 'Foo-Bar',
                'foo_bar' => 'Foo_bar',
                'foo&bar' => 'Foo&Bar',
                'foo/bar' => 'Foo/Bar'
            );
            foreach ($cmp as $key => $value)
                $this->assertEquals(Titlecase($key), $value);
        }

        public function testCamelCase()
        {
            $cmp = array(
                'test' => 'Test',
                'foo bar' => 'FooBar',
                'foo-bar' => 'FooBar',
                'foo_bar' => 'Foo_bar',
                'foo&bar' => 'Foo&Bar',
                'foo/bar' => 'Foo/Bar'
            );
            foreach ($cmp as $key => $value)
                $this->assertEquals(camelCase($key), $value);
        }

        public function testStartswith()
        {
            $this->assertTrue(startswith('abc', 'a'));
            $this->assertTrue(startswith('abc', 'ab'));
            $this->assertTrue(startswith('abc', 'abc'));
            $this->assertFalse(startswith('abc', 'abcd'));
            try {
                $this->assertTrue(startswith('a', ''));
                $this->assertTrue(false);
            } catch (Exception $e) {}
        }

        public function testEndswith()
        {
            $this->assertTrue(endswith('abc', 'c'));
            $this->assertTrue(endswith('abc', 'bc'));
            $this->assertTrue(endswith('abc', 'abc'));
            $this->assertFalse(endswith('abc', 'abcd'));
            try {
                $this->assertTrue(endswith('a', ''));
                $this->assertTrue(false);
            } catch (Exception $e) {}
        }

        public function testIsEmpty()
        {
            $this->assertTrue(isEmpty(array()));
            $this->assertTrue(isEmpty(''));
            $this->assertTrue(isEmpty(0));
            $this->assertTrue(isEmpty(0.0));
            $this->assertTrue(isEmpty(null));
            $this->assertFalse(isEmpty(array(0)));
            $this->assertFalse(isEmpty(array(0, 3)));
            $this->assertFalse(isEmpty('string'));
            $this->assertFalse(isEmpty(3));
            $this->assertFalse(isEmpty(3.5));
            $this->assertFalse(isEmpty(new Exception()));
        }
    }

    class StackTesting extends TestingAdapter {

        public function test_basic()
        {
            $stack = new Stack();
            for ($i=0; $i<200; $i++)
                $stack->push($i);
            $stack->push("Hello World");
            for ($i=0; $i<200; $i++)
                $stack->pop();
            $stack->pop();
            $this->assertEquals($stack->getStackSize(), 0);

            $stack->clear();
            $this->assertEquals($stack->count(), 0);
        }

        public static function clbk_smallerthree($value)
        {
            return $value < 3;
        }

        public static function clbk_triple($value)
        {
            return $value * 3;
        }

        public static function clbk_and($a, $b)
        {
            return $a & $b;
        }

        public static function clbk_xor($a, $b)
        {
            return $a ^ $b;
        }

        public static function clbk_minus($a, $b)
        {
            return $a - $b;
        }

        public static function clbk_cmp($a, $b)
        {
            return strcmp($a[0], $b[0]);
        }

        // test constructor and push

        public function test_max_size()
        {
            $stack = new Stack(2);
            $stack->push(1);
            $stack->push(2);
            try {
                $stack->push(3);
                $error = true;
            } catch (OverflowException $e) {
                $error = false;
            }
            if ($error)
                throw new UnexpectedValueException('I expected the stack to overflow');
        }

        public function test_max_size2()
        {
            $stack = new Stack(2);
            $stack->push(1);
            $stack->pop();
            $stack->push(1, 2);
            $stack->pop();
            $stack->pop();
            try {
                $stack->push(1, 2, 3);
                $error = true;
            } catch (OverflowException $e) {
                $error = false;
            }
            if ($error)
                throw new UnexpectedValueException('I expected the stack to overflow');
        }

        public function test_order()
        {
            // Test, ORDER_LIST is default setting
            $stack = new Stack(Stack::INFINITE_SIZE);
            $stack->push(1, 2, 3);
            $array = $stack->iterate();
            $this->assertEquals($stack->count(), 3);
            $this->assertEquals($array[0], 1);
            $this->assertEquals($array[1], 2);
            $this->assertEquals($array[2], 3);
        }

        public function test_order2()
        {
            $stack = new Stack(Stack::INFINITE_SIZE, Stack::ORDER_LIST);
            $stack->push(1, 2, 3);
            $array = $stack->iterate();
            $this->assertEquals($array[0], 1);
            $this->assertEquals($array[1], 2);
            $this->assertEquals($array[2], 3);
        }

        public function test_order3()
        {
            $stack = new Stack(Stack::INFINITE_SIZE, Stack::ORDER_STACK);
            $stack->push(1, 2, 3);
            $array = $stack->iterate();
            $this->assertEquals($array[0], 3);
            $this->assertEquals($array[1], 2);
            $this->assertEquals($array[2], 1);
        }

        // test chunk

        public function test_chunk()
        {
            $stack = new Stack(Stack::INFINITE_SIZE, Stack::ORDER_LIST);
            $stack->push(1, 2, 3, 4, 5, 6, 7);
            $expect = array(array(1, 2), array(3, 4), array(5, 6), array(7));
            $this->assertEquals($stack->chunk(2), $expect);
        }

        public function test_chunk2()
        {
            $stack = new Stack(Stack::INFINITE_SIZE, Stack::ORDER_STACK);
            $stack->push(1, 2, 3, 4, 5, 6, 7);
            $expect = array(array(7, 6), array(5, 4), array(3, 2), array(1));
            $this->assertEquals($stack->chunk(2), $expect);
        }

        // test clear

        public function test_clear()
        {
            $stack = new Stack(Stack::INFINITE_SIZE, Stack::ORDER_LIST);
            $stack->push(1);
            $stack->push(2);
            $this->assertEquals($stack->count(), 2);
            $stack->clear();
            $this->assertEquals($stack->count(), 0);
        }

        // test copy

        public function test_copy()
        {
            $stack = new Stack(Stack::INFINITE_SIZE, Stack::ORDER_LIST);
            $stack->push(1);
            $stack->push(3);

            $stack2 = clone $stack;
            $this->assertEquals($stack2->count(), 2);
            $this->assertEquals($stack2->pop(), 3);
        }

        // test count and getStackSize()

        public function test_count()
        {
            $stack = new Stack(Stack::INFINITE_SIZE, Stack::ORDER_LIST);
            $stack->push(1);
            $stack->push(2);
            $this->assertEquals(2, $stack->count(), $stack->getStackSize());
            $stack->pop();
            $this->assertEquals(1, $stack->count(), $stack->getStackSize());
            $stack->clear();
            $this->assertEquals(0, $stack->count(), $stack->getStackSize());
        }

        // test diff

        public function test_diff()
        {
            $stack1 = new Stack();
            $stack2 = new Stack();
            $stack3 = new Stack();

            $stack1->push(1);
            $stack2->push(1);
            $stack1->push(1);
            $stack2->push(3);

            $this->assertEquals($stack1->diff($stack2)->count(), 0);

            $stack1->pop();
            $stack1->push(2);

            $ref = new Stack();
            $ref->push(2);
            $this->assertTrue($stack1->diff($stack2)->equals($ref));
        }

        public function test_diff2()
        {
            $stack1 = new Stack();
            $stack2 = new Stack();

            $stack1->push(1);

            $ref = new Stack();
            $ref->push(1);
            $this->assertTrue($stack1->diff($stack2)->equals($ref));
        }

        public function test_diff3()
        {
            $stack1 = new Stack(Stack::INFINITE_SIZE, Stack::ORDER_LIST);
            $stack1->push(1, 2, 3, 4, 5);
            $stack2 = new Stack(Stack::INFINITE_SIZE, Stack::ORDER_LIST);
            $stack2->push(3);

            $ref = new Stack(Stack::INFINITE_SIZE, Stack::ORDER_LIST);
            $ref->push(1, 2, 4, 5);

            $this->assertTrue($stack1->diff($stack2)->equals($ref));
        }

        // test equals and __toString

        public function test_equals()
        {
            $stack1 = new Stack();
            $stack2 = new Stack();

            $stack1->push(1);
            $stack2->push(1);
            $stack1->push(array(2, 3));
            $stack2->push(array(3, 4));

            $this->assertFalse($stack1->equals($stack2));
            $this->assertFalse($stack1->__toString() === $stack2->__toString());

            $stack1->pop();
            $stack2->pop();

            $this->assertTrue($stack1->equals($stack2));
            $this->assertEquals($stack1->__toString(), $stack2->__toString());
        }

        // test exists

        public function test_exists()
        {
            $stack = new Stack();
            $stack->push(1);
            $stack->push(2);

            $this->assertFalse($stack->exists(3));
            $this->assertTrue($stack->exists(2));

            $stack = new Stack();
            $stack->push(array());
            $stack->push(array(1, 2));

            $this->assertFalse($stack->exists(2));
            $this->assertTrue($stack->exists(array(1, 2)));
        }

        // test index

        public function test_index()
        {
            $stack = new Stack();
            $stack->push(1);
            $stack->push(2);

            $this->assertEquals($stack->index(0), 1);
            $this->assertEquals($stack->index(1), 2);
        }

        // test intersect

        public function test_intersect()
        {
            $stack1 = new Stack();
            $stack2 = new Stack();
            $array1 = array(4, 5);

            $stack1->push(1, 4, 6);
            $stack2->push(2, 3);

            $ref = new Stack();
            $ref->push(4);

            $this->assertTrue($stack1->intersect($stack2)->equals(new Stack()));
            $this->assertTrue($stack1->intersect($array1)->equals($ref));
        }

        // test iterate

        public function test_iterate()
        {
            $stack = new Stack(Stack::INFINITE_SIZE, Stack::ORDER_LIST);
            $stack->push(1, 2, 3);

            $expect = array(1, 2, 3);
            $i = 0;
            foreach ($stack->iterate() as $value) {
                $this->assertEquals($value, $expect[$i++]);
            }
        }

        public function test_order_iterate2()
        {
            $stack = new Stack(Stack::INFINITE_SIZE, Stack::ORDER_STACK);
            $stack->push(1, 2, 3);
            $this->assertEquals($stack->iterate(), array(3, 2, 1));
        }

        // test iterateFiltered

        public function test_order_iterateFiltered()
        {
            $stack = new Stack(Stack::INFINITE_SIZE, Stack::ORDER_LIST);
            $stack->push(1, 2, 3, 4, 5, 6);
            $this->assertEquals($stack->iterateFiltered(array(3), 'array'),
                array(1, 2, 4, 5, 6));
            $this->assertEquals($stack->iterateFiltered(array(3, 4), 'array'),
                array(1, 2, 5, 6));

            $callback = array($this, 'clbk_smallerthree');
            $this->assertEquals(
                $stack->iterateFiltered($callback, 'callback'),
                array(1, 2)
            );
        }

        // test map

        public function test_map()
        {
            $stack = new Stack(Stack::INFINITE_SIZE, Stack::ORDER_LIST);
            $stack->push(1, 2, 3, 4, 5);
            $stack->map(array($this, 'clbk_triple'));

            $ref = new Stack(Stack::INFINITE_SIZE, Stack::ORDER_LIST);
            $ref->push(3, 6, 9, 12, 15);

            $this->assertTrue($stack->equals($ref));
        }

        // test pad
        
        public function test_pad()
        {
            $stack = new Stack(Stack::INFINITE_SIZE, Stack::ORDER_LIST);
            $stack->push(1, 2);
            $stack->pad(4, 1);

            $ref = new Stack(Stack::INFINITE_SIZE, Stack::ORDER_LIST);
            $ref->push(1, 2, 1, 1);

            $this->assertTrue($stack->equals($ref));

            $stack->pad(2, 1);
            $ref->pop(2);

            $this->assertTrue($stack->equals($ref));

            $stack->pad(2, 1);

            $this->assertTrue($stack->equals($ref));

            $stack->pad(5, 2);
            $ref->push(2, 2, 2);

            $this->assertTrue($stack->equals($ref));
        }

        // test pop and push

        public function test_pop()
        {
            $stack = new Stack(Stack::INFINITE_SIZE, Stack::ORDER_LIST);
            $stack->push(1, 2, 3, 4);
            $this->assertEquals($stack->pop(), 4);

            $stack = new Stack(Stack::INFINITE_SIZE, Stack::ORDER_LIST);
            $stack->push(1, 2, 3, 4);
            $this->assertEquals($stack->pop(3), array(2, 3, 4));

            $stack = new Stack(Stack::INFINITE_SIZE, Stack::ORDER_STACK);
            $stack->push(1, 2, 3, 4);
            $this->assertEquals($stack->pop(3), array(4, 3, 2));

            $stack = new Stack(Stack::INFINITE_SIZE, Stack::ORDER_STACK);
            $stack->push(1);
            $stack->pop();
            try {
                $stack->pop();
                $error = true;
            } catch (UnderflowException $e) {
                $error = false;
            }
            if ($error)
                throw new UnexpectedValueException('I expected the stack to underflow');
        }

        // test pushRev

        public function test_pushRev()
        {
            $stack = new Stack(Stack::INFINITE_SIZE, Stack::ORDER_LIST);
            $stack->pushRev(1);
            $this->assertEquals($stack->pop(), 1);

            $stack->pushRev(1, 2, 3, 4);
            $this->assertEquals($stack->pop(), 1);
            $this->assertEquals($stack->pop(), 2);
            $this->assertEquals($stack->pop(), 3);
            $this->assertEquals($stack->pop(), 4);

            $stack = new Stack(2, Stack::ORDER_LIST);
            try {
                $stack->pushRev(1, 2, 3);
                $error = true;
            } catch (OverflowException $e) {
                $error = false;
            }
            if ($error)
                throw new UnexpectedValueException('I expected the stack to overflow');
        }

        // test pushElement

        public function test_pushElement()
        {
            $stack = new Stack(Stack::INFINITE_SIZE, Stack::ORDER_LIST);
            $stack->pushElement(1, 2, 3, 4);
            $this->assertEquals($stack->pop(), 4);
            $this->assertEquals($stack->pop(), 3);
            $this->assertEquals($stack->pop(), 2);
            $this->assertEquals($stack->pop(), 1);
            $this->assertEquals($stack->count(), 0);

            $stack = new Stack(Stack::INFINITE_SIZE, Stack::ORDER_STACK);
            $stack->pushElement(1, 2, 3, 4);
            $this->assertEquals($stack->pop(), 1);
            $this->assertEquals($stack->pop(), 2);
            $this->assertEquals($stack->pop(), 3);
            $this->assertEquals($stack->pop(), 4);
            $this->assertEquals($stack->count(), 0);
        }

        // test reduce

        public function test_reduce()
        {
            $stack = new Stack(Stack::INFINITE_SIZE, Stack::ORDER_LIST);
            $stack->push(1, 2, 3, 4);

            $result = $stack->reduce(array($this, 'clbk_and'));
            $this->assertEquals($result, 0);

            $result = $stack->reduce(array($this, 'clbk_xor'));
            $this->assertEquals($result, 4);

            $result = $stack->reduce(array($this, 'clbk_minus'));
            $this->assertEquals($result, -8);

            $stack->order = Stack::ORDER_STACK;
            $result = $stack->reduce(array($this, 'clbk_minus'));
            $this->assertEquals($result, -2);
        }

        // test replace

        public function test_replace()
        {
            $stack = new Stack();
            $stack->push(1);
            $stack->push(2);
            $stack->push(3);

            $stack->replace(1, 3);

            $ref = new Stack();
            $ref->push(1);
            $ref->push(3);
            $ref->push(3);
            $this->assertTrue($stack->equals($ref));

            try {
                $stack->replace(4, 0);
                $error = true;
            } catch (OutOfRangeException $e) {
                $error = false;
            }
            if ($error)
                throw new UnexpectedValueException('I expected the stack to overflow');
        }

        // test replaceElements

        public function test_replaceElements()
        {
            $stack = new Stack();
            $stack->push(1);
            $stack->push(2);
            $stack->push(3);

            $stack->replaceElements(array(1 => "a", 2 => 2.0, 8 => "\n"));

            $ref = new Stack();
            $ref->push("a");
            $ref->push(2.0);
            $ref->push(3);

            $this->assertTrue($stack->equals($ref));
        }

        // test reverse

        public function test_reverse()
        {
            $stack = new Stack(Stack::INFINITE_SIZE, Stack::ORDER_LIST);
            $stack->push(1, 2, 3, 4);
            $stack->reverse();
            $this->assertEquals($stack->pop(), 1);
            $this->assertEquals($stack->pop(), 2);
            $this->assertEquals($stack->pop(), 3);
            $this->assertEquals($stack->pop(), 4);
        }

        // test setName and __toString

        public function test_setName()
        {
            $stack = new Stack(Stack::INFINITE_SIZE, Stack::ORDER_LIST);
            $stack->setName('MyStack');
            $string = $stack->__toString();
            $this->assertTrue(strpos($string, 'MyStack') !== false);
        }

        // test shift

        public function test_shift()
        {
            $stack = new Stack();
            $stack->push(1);
            $this->assertEquals($stack->shift(), array(1));

            $stack->push(1);
            $stack->push(2);
            $this->assertEquals($stack->shift(), array(1));
            $this->assertEquals($stack->shift(), array(2));

            $stack->push(1);
            $stack->push(2);
            $this->assertEquals($stack->shift(2), array(1, 2));
        }

        // test shuffle

        public function test_shuffle()
        {
            $stack = new Stack();
            $stack->push(1);
            $stack->shuffle();
            $this->assertEquals($stack->pop(), 1);

            $stack->push(5);
            $stack->push(6);
            $stack->shuffle();
            $val = $stack->pop();
            $this->assertTrue($val == 5 || $val == 6);
        }

        // test slice

        public function test_slice()
        {
            $stack = new Stack(Stack::INFINITE_SIZE, Stack::ORDER_LIST);
            $stack->push(0, 1, 2, 3, 4, 5, 6, 7, 8);

            $this->assertEquals($stack->slice(0, 3), array(0, 1, 2));
            $this->assertEquals($stack->slice(1, 5), array(1, 2, 3, 4, 5));

            $stack->order = Stack::ORDER_STACK;
            $this->assertEquals($stack->slice(0, 3), array(8, 7, 6));
            $this->assertEquals($stack->slice(1, 5), array(7, 6, 5, 4, 3));

            $this->assertEquals($stack->slice(7, 2), array(1, 0));
            $this->assertEquals($stack->slice(7, 3), array(1, 0));

            $stack->order = Stack::ORDER_LIST;
            $this->assertEquals($stack->slice(7, 2), array(7, 8));
            $this->assertEquals($stack->slice(7, 3), array(7, 8));

            $this->assertEquals($stack->slice(-3, 3), array(6, 7, 8));
            $this->assertEquals($stack->slice(-5, -2), array(4, 5, 6));
        }

        // test splice

        public function test_splice()
        {
            $stack = new Stack();
            $stack->push(0);
            $stack->push(1);
            $stack->push(2);
            $stack->push(3);
            $stack->push(4);
            $stack->push(5);

            $stack->splice(0, 3, array(1));

            $ref = new Stack();
            $ref->push(1);
            $ref->push(3);
            $ref->push(4);
            $ref->push(5);

            $this->assertTrue($stack->equals($ref));
        }

        public function test_splice2()
        {
            $stack = new Stack();
            $stack->push(0);
            $stack->push(1);
            $stack->push(2);
            $stack->push(3);
            $stack->push(4);
            $stack->push(5);

            $stack->splice(-5, -2, array(1, 6));

            $ref = new Stack();
            $ref->push(0);
            $ref->push(1);
            $ref->push(6);
            $ref->push(4);
            $ref->push(5);

            $this->assertTrue($stack->equals($ref));
        }

        // test sort

        public function test_sort()
        {
            $stack = new Stack();
            $stack->push(0);
            $stack->push(4);
            $stack->push(3);
            $stack->push(5);

            $stack->sort();
            $this->assertEquals($stack->pop(), 5);
            $this->assertEquals($stack->pop(), 4);
            $this->assertEquals($stack->pop(), 3);
            $this->assertEquals($stack->pop(), 0);
        }

        public function test_sort2()
        {
            $stack = new Stack();
            $stack->push(array('duck'));
            $stack->push(array('monkey'));
            $stack->push(array('zebra'));
            $stack->push(array('frog'));

            $stack->sort(array($this, 'clbk_cmp'));
            $this->assertEquals($stack->pop(), array('zebra'));
            $this->assertEquals($stack->pop(), array('monkey'));
            $this->assertEquals($stack->pop(), array('frog'));
            $this->assertEquals($stack->pop(), array('duck'));
        }

        // test unique

        public function test_unique()
        {
            $stack = new Stack();
            $stack->push(1, 1, 1);

            $stack->unique();
            $this->assertEquals($stack->pop(), 1);
            $this->assertEquals($stack->getStackSize(), 0);
        }

        // test unshift

        public function test_unshift()
        {
            $stack = new Stack(Stack::INFINITE_SIZE, Stack::ORDER_LIST);
            $stack->push(1, 2, 3);
            $stack->unshift(array(-1, 0));
            $this->assertEquals($stack->pop(5), array(-1, 0, 1, 2, 3));

            $stack->order = Stack::ORDER_STACK;
            $stack->push(1, 2, 3);
            $stack->unshift(array(0, -1));
            $this->assertEquals($stack->pop(5), array(3, 2, 1, 0, -1));
        }
    }

    class NotificationsTesting extends TestingAdapter {

        public function test_simple()
        {
            $notify = new Notifications();
            $notify->push("No error occured", 3);
            $notify->push("Deprecation Warning", 2);
            $this->assertEquals($notify->count(), 2);

            $notify->pop();
            $notify->push("Fatal error. Abort", 1);
            foreach ($notify->iterate() as $key => $value)
            {
                list($errmsg, $class) = $value;
                $this->assertTrue(is_string($errmsg));
                $this->assertTrue(is_int($class));
            }
            $this->assertEquals($notify->count(), 2);
        }
    }

    $test = new FunctionsTesting();
    $test->run();

    $test = new StackTesting();
    $test->run();

    $test = new NotificationsTesting();
    $test->run();
?>
