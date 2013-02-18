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

        public function testE()
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

        public function testGetItem()
        {
            $array1 = array('foo' => 'bar');
            $array2 = array();

            $this->assertEquals(getItem($array1, 'foo'), 'bar');
            $this->assertEquals(getItem($array1, 'foo', 1), 'bar');
            $this->assertEquals(getItem($array1, 'baz'), NULL);
            $this->assertEquals(getItem($array1, 'baz', 1), 1);
            $this->assertEquals(getItem($array2, 'foo'), NULL);
            $this->assertEquals(getItem($array2, 'foo', 6), 6);
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
            $this->assertTrue(startswith('a', ''));
        }

        public function testEndswith()
        {
            $this->assertTrue(endswith('abc', 'c'));
            $this->assertTrue(endswith('abc', 'bc'));
            $this->assertTrue(endswith('abc', 'abc'));
            $this->assertFalse(endswith('abc', 'abcd'));
            $this->assertTrue(endswith('a', ''));
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

        public function testInc()
        {
            $int = array(1, 2, 4, 8, 16, 32, 64, 128, 256);
            $inced = array(2, 3, 5, 9, 17, 33, 65, 129, 257);
            foreach ($int as &$value)
            {
                $old = $value;
                $this->assertTrue(inc($value) === $old + 1);
            }
            $this->assertTrue($int === $inced);
        }

        public function testIncrementSuffix()
        {
            $this->assertEquals(incrementSuffix('foo'), 'foo');
            $this->assertEquals(incrementSuffix('bar 0'), 'bar 1');
            $this->assertEquals(incrementSuffix('Foo  1'), 'Foo  2');
            $this->assertEquals(incrementSuffix('f	3'), 'f	4');
            $this->assertEquals(incrementSuffix('bar0'), 'bar0');
            $this->assertEquals(incrementSuffix('foo 10'), 'foo 11');
            $this->assertEquals(incrementSuffix('bar 424242'), 'bar 424243');
        }

        public function testUniqueId()
        {
            $tests = 2000;
            $ids = array();
            for ($i=0; $i<$tests; $i++)
                $ids[] = uniqueId();
            $this->assertEquals(count(array_unique($ids)), $tests);
        }

        public function testWhitelist()
        {
            $array = array(1 => 'foo', 'bar' => 'baz', 'baz' => '1234');
            $this->assertEquals(whitelist($array, array_keys($array)), $array);
            $this->assertEquals(whitelist($array, array(1, 'baz')), array(1 => 'foo', 'baz' => '1234'));
            $this->assertEquals(whitelist($array, array()), array());
        }

        public function testBlacklist()
        {
            $array = array(1 => 'foo', 'bar' => 'baz', 'baz' => '1234');
            $this->assertEquals(blacklist($array, array_keys($array)), array());
            $this->assertEquals(blacklist($array, array(1, 'baz')), array('bar' => 'baz'));
            $this->assertEquals(blacklist($array, array()), $array);
        }
    }

    class StackTesting extends TestingAdapter {

        public function testBasic()
        {
            $stack = new Stack();
            for ($i=0; $i<200; $i++)
                $stack->push($i);
            $stack->push('Hello')->push('World');
            for ($i=0; $i<200; $i++)
                $stack->pop();
            $this->assertEquals($stack->pop(), 1);
            $this->assertEquals($stack->pop(), 0);
            $this->assertEquals($stack->getStackSize(), 0);

            $stack->clear();
            $this->assertEquals($stack->count(), 0);
        }

        public static function clbkSmallerThree($value)
        {
            return $value < 3;
        }

        public static function clbkTriple($value)
        {
            return $value * 3;
        }

        public static function clbkAnd($a, $b)
        {
            return $a & $b;
        }

        public static function clbkXor($a, $b)
        {
            return $a ^ $b;
        }

        public static function clbkMinus($a, $b)
        {
            return $a - $b;
        }

        public static function clbkCmp($a, $b)
        {
            return strcmp($a[0], $b[0]);
        }

        // test constructor and push

        public function testMaxSize()
        {
            $stack = new Stack(2);
            $stack->push(1);
            $stack->push(2);
            try {
                $stack->push(3);
                throw new UnexpectedValueException('I expected the stack to overflow');
            } catch (OverflowException $e) { }
        }

        public function testMaxSize2()
        {
            $stack = new Stack(2);
            $stack->push(1);
            $stack->pop();
            $stack->push(1, 2);
            $stack->pop();
            $stack->pop();
            try {
                $stack->push(1, 2, 3);
                throw new UnexpectedValueException('I expected the stack to overflow');
            } catch (OverflowException $e) { }
        }

        public function testOrder()
        {
            $stack = new Stack(Stack::INFINITE_SIZE, Stack::ORDER_LIST);
            $stack->push(1, 2, 3);
            $array = $stack->iterate();
            $this->assertEquals($array[0], 1);
            $this->assertEquals($array[1], 2);
            $this->assertEquals($array[2], 3);
        }

        public function testOrder2()
        {
            $stack = new Stack(Stack::INFINITE_SIZE, Stack::ORDER_STACK);
            $stack->push(1, 2, 3);
            $array = $stack->iterate();
            $this->assertEquals($array[0], 3);
            $this->assertEquals($array[1], 2);
            $this->assertEquals($array[2], 1);
        }

        // test chunk

        public function testChunk()
        {
            $stack = new Stack(Stack::INFINITE_SIZE, Stack::ORDER_LIST);
            $stack->push(1, 2, 3, 4, 5, 6, 7);
            $expect = array(array(1, 2), array(3, 4), array(5, 6), array(7));
            $this->assertEquals($stack->chunk(2), $expect);
        }

        public function testChunk2()
        {
            $stack = new Stack(Stack::INFINITE_SIZE, Stack::ORDER_STACK);
            $stack->push(1, 2, 3, 4, 5, 6, 7);
            $expect = array(array(7, 6), array(5, 4), array(3, 2), array(1));
            $this->assertEquals($stack->chunk(2), $expect);
        }

        // test clear

        public function testClear()
        {
            $stack = new Stack(Stack::INFINITE_SIZE, Stack::ORDER_LIST);
            $stack->push(1);
            $stack->push(2);
            $this->assertEquals($stack->count(), 2);
            $stack->clear();
            $this->assertEquals($stack->count(), 0);
        }

        // test copy

        public function testClone()
        {
            $stack = new Stack(42, Stack::ORDER_STACK);
            $stack->push('foo');
            $stack->push('bar');
            $stack->setName('BazStack');

            $stack2 = clone $stack;
            $this->assertEquals($stack2->getMaxSize(), $stack->getMaxSize());
            $this->assertEquals($stack2->order, $stack->order);
            $this->assertEquals($stack2->iterate(), $stack->iterate());
            $this->assertEquals($stack2->getName(), $stack->getName());
        }

        // test copy

        public function testCopy()
        {
            $stack = new Stack(42, Stack::ORDER_LIST);
            $stack->push(1);
            $stack->push(3);

            $stack2 = clone $stack;
            $this->assertEquals($stack2->count(), 2);
            $this->assertEquals($stack2->pop(), 3);
        }

        // test count and getStackSize()

        public function testCount()
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

        public function testCount2()
        {
            $stack = new Stack(2);
            $this->assertEquals(0, $stack->count(), $stack->getStackSize());
            $stack->push(1);
            $this->assertEquals(1, $stack->count(), $stack->getStackSize());
            $stack->push(2);
            $this->assertEquals(2, $stack->count(), $stack->getStackSize());

            try {
                $stack->push(5);
                throw new UnexpectedValueException('I expected the stack to overflow');
            } catch (OverflowException $e) { }

            $this->assertEquals(2, $stack->count(), $stack->getStackSize());
        }

        // test create

        public function testCreate()
        {
            $stack = new Stack();
            $stack->setName('Foobar');
            $stack2 = $stack->create();

            $this->assertEquals($stack->count(), $stack2->count());
            $this->assertEquals($stack->iterate(), $stack2->iterate());
            $this->assertEquals($stack2->getName(), 'Foobar 2');
        }

        public function testCreate2()
        {
            $stack = new Stack(Stack::INFINITE_SIZE, Stack::ORDER_LIST);
            $stack->push(NULL);
            $stack->push(8);
            $stack->push('Foobar');
            $stack->setName('Foobaz');
            $stack2 = $stack->create();

            $this->assertEquals($stack->count(), $stack2->count());
            $this->assertEquals($stack->iterate(), $stack2->iterate());
            $this->assertEquals($stack->getName().' 2', $stack2->getName());
        }

        public function testCreate3()
        {
            $stack = new Stack(42, Stack::ORDER_STACK);
            $stack->push(NULL);
            $stack->push(8);
            $stack->push('Foobar');
            $stack->setName('Foobaz');
            $stack2 = $stack->create();

            $this->assertEquals($stack->count(), $stack2->count());
            $this->assertEquals($stack->iterate(), $stack2->iterate());
            $this->assertEquals($stack->getName().' 2', $stack2->getName());
        }

        public function testCreate4()
        {
            $stack = new Stack();
            $stack2 = $stack->create(42, array(1, 2, 3), Stack::ORDER_STACK, 'Foobar 3');

            $this->assertEquals($stack2->getMaxSize(), 42);
            $this->assertEquals($stack2->pop(3), array(3, 2, 1));
            $this->assertEquals($stack2->getName(), 'Foobar 3');
        }

        public function testCreate5()
        {
            $stack = new Stack();
            $stack->setName('Foobar');
            $stack2 = $stack->create();
            $stack3 = $stack2->create();
            $stack4 = $stack3->create();
            $stack5 = $stack4->create();
            $stack6 = $stack5->create();
            $stack7 = $stack6->create();
            $stack8 = $stack7->create();

            $this->assertEquals($stack8->getName(), 'Foobar 8');
        }

        // test diff and diffAssoc

        public function testDiff()
        {
            $stack1 = new Stack();
            $stack2 = new Stack();
            $stack3 = new Stack();

            $stack1->push(1);
            $stack1->push(1);
            $stack2->push(1);
            $stack2->push(3);

            $this->assertEquals($stack2->diff($stack1)->iterate(), array(3));

            $stack2->pop();

            $this->assertEquals($stack1->diff($stack2)->iterate(), array());
            $this->assertTrue($stack2->diff($stack3)->equals($stack2));

            $stack2->pop();

            $this->assertEquals($stack2->diff($stack3)->iterate(), array());
        }

        public function testDiff2()
        {
            $stack1 = new Stack();
            $stack2 = new Stack();

            $stack1->push(1);

            $ref = new Stack();
            $ref->push(1);
            $this->assertTrue($stack1->diff($stack2)->equals($ref));
        }

        public function testDiff3()
        {
            $stack1 = new Stack(Stack::INFINITE_SIZE, Stack::ORDER_LIST);
            $stack1->push(1, 2, 3, 4, 5);
            $stack2 = new Stack(Stack::INFINITE_SIZE, Stack::ORDER_LIST);
            $stack2->push(3);

            $ref = new Stack(Stack::INFINITE_SIZE, Stack::ORDER_LIST);
            $ref->push(1, 2, 4, 5);

            $this->assertTrue($stack1->diff($stack2)->equals($ref));
        }

        public function testAssocDiff()
        {
            $stack1 = new Stack(Stack::INFINITE_SIZE, Stack::ORDER_LIST);
            $stack2 = new Stack(Stack::INFINITE_SIZE, Stack::ORDER_LIST);
            $stack3 = new Stack(Stack::INFINITE_SIZE, Stack::ORDER_LIST);

            $stack1->push(1, 1, 2, 3, 5);
            $stack2->push(0, 1, 1, 2, 3);

            $this->assertEquals($stack2->diffAssoc($stack1)->iterate(), array(0, 1, 2, 3));
            $this->assertEquals($stack1->diffAssoc($stack2)->iterate(), array(1, 2, 3, 5));
            $this->assertTrue($stack2->diffAssoc(array())->equals($stack2));
        }


        // test equals

        public function testEquals()
        {
            $stack1 = new Stack();
            $stack2 = new Stack();

            $stack1->push(1);
            $stack2->push(1);
            $stack1->push(array(2, 3));
            $stack2->push(array(3, 4));

            $this->assertFalse($stack1->equals($stack2));

            $stack1->pop();
            $stack2->pop();

            $this->assertTrue($stack1->equals($stack2));
        }

        // test exists

        public function testExists()
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

        // test getMaxSize

        public function testGetMaxSize()
        {
            $stack = new Stack(2);
            $stack->push(1);

            $this->assertEquals($stack->getMaxSize(), 2);

            $stack2 = clone $stack;
            $this->assertEquals($stack2->getMaxSize(), 2);
        }

        // test getName

        public function testGetName()
        {
            $stack = new Stack();
            $stack->setName('Foobar');
            $this->assertEquals($stack->getName(), 'Foobar');
        }

        // test index

        public function testIndex()
        {
            $stack = new Stack(42, Stack::ORDER_LIST);
            $stack->push(1, 2);
            $this->assertEquals($stack->index(0), 1);
            $this->assertEquals($stack->index(1), 2);
        }

        public function testIndex2()
        {
            $stack = new Stack(Stack::INFINITE_SIZE, Stack::ORDER_STACK);
            $stack->push(1, 2, 3, 4);
            $this->assertEquals($stack->index(0), 4);
            $this->assertEquals($stack->index(1), 3);
            $this->assertEquals($stack->index(2), 2);
            $this->assertEquals($stack->index(3), 1);
        }

        // test intersect

        public function testIntersect()
        {
            $stack1 = new Stack();
            $stack2 = new Stack();
            $stack3 = new Stack();
            $array1 = array(4, 5);

            $stack1->push(1, 4, 6);
            $stack2->push(2, 3);
            $stack3->push(4);

            $this->assertTrue($stack1->intersect($stack1)->equals($stack1));
            $this->assertTrue($stack1->intersect($stack2)->equals(new Stack()));
            $this->assertTrue($stack1->intersect($stack3)->equals($stack3));
            $this->assertTrue($stack1->intersect($array1)->equals($stack3));

            $this->assertTrue($stack2->intersect($stack1)->equals(new Stack()));
            $this->assertTrue($stack2->intersect($stack2)->equals($stack2));
            $this->assertTrue($stack2->intersect($stack3)->equals(new Stack()));
            $this->assertTrue($stack2->intersect($array1)->equals(new Stack()));

            $this->assertTrue($stack3->intersect($stack1)->equals($stack3));
            $this->assertTrue($stack3->intersect($stack2)->equals(new Stack()));
            $this->assertTrue($stack3->intersect($stack3)->equals($stack3));
            $this->assertTrue($stack3->intersect($array1)->equals($stack3));
        }

        // test iterate

        public function testIterate()
        {
            $stack = new Stack(Stack::INFINITE_SIZE, Stack::ORDER_LIST);
            $stack->push(1, 2, 3);
            $this->assertEquals($stack->iterate(), array(1, 2, 3));
        }

        public function testIterate2()
        {
            $stack = new Stack(Stack::INFINITE_SIZE, Stack::ORDER_STACK);
            $stack->push(1, 2, 3);
            $this->assertEquals($stack->iterate(), array(3, 2, 1));
        }

        // test iterateFiltered

        public function testIterateFiltered()
        {
            $stack = new Stack(Stack::INFINITE_SIZE, Stack::ORDER_LIST);

            // filter with array
            $stack->push(1, 2, 3, 4, 5, 6);
            $this->assertEquals($stack->iterateFiltered(array(3), 'array'),
                array(1, 2, 4, 5, 6));
            $this->assertEquals($stack->iterateFiltered(array(3, 4), 'array'),
                array(1, 2, 5, 6));

            // filter with callback
            $callback = array($this, 'clbkSmallerThree');
            $this->assertEquals(
                $stack->iterateFiltered($callback, 'callback'),
                array(1, 2)
            );
        }

        // test iteratePopping

        public function testIteratePopping()
        {
            $stack = new Stack(Stack::INFINITE_SIZE, Stack::ORDER_LIST);
            $array = array(1, 2, 3, 4, 5);

            foreach ($array as $value)
            {
                $stack->push($value);
            }

            $i = 0;
            foreach ($stack->iteratePopping() as $value)
            {
                $this->assertEquals($value, $array[$i++]);
            }
            $this->assertEquals($stack->getStackSize(), 0);
        }

        // test map

        public function testMap()
        {
            $stack = new Stack();
            $stack->push(1, 2, 3, 4, 5);

            $stack->map(array($this, 'clbkTriple'));
            $this->assertTrue($stack->equals(array(3, 6, 9, 12, 15)));

            $func = create_function('$arg', 'return (int)($arg / 2);');
            $stack->map($func);
            $this->assertTrue($stack->equals(array(1, 3, 4, 6, 7)));
        }

        // test pad
        
        public function testPad()
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

        public function testPop()
        {
            $stack = new Stack(Stack::INFINITE_SIZE, Stack::ORDER_LIST);
            $stack->push(1, 2, 3, 4);
            $this->assertEquals($stack->pop(), 4);
        }

        public function testPop2()
        {
            $stack = new Stack(Stack::INFINITE_SIZE, Stack::ORDER_LIST);
            $stack->push(1, 2, 3, 4);
            $this->assertEquals($stack->pop(3), array(2, 3, 4));
        }

        public function testPop3()
        {
            $stack = new Stack(Stack::INFINITE_SIZE, Stack::ORDER_STACK);
            $stack->push(1, 2, 3, 4);
            $this->assertEquals($stack->pop(3), array(4, 3, 2));
        }

        public function testPop4()
        {
            $stack = new Stack(Stack::INFINITE_SIZE, Stack::ORDER_STACK);
            $stack->push(1);
            $stack->pop();
            try {
                $stack->pop();
                throw new UnexpectedValueException('I expected the stack to underflow');
            } catch (UnderflowException $e) { }
        }

        // push was tested implicitly
        // test pushRev

        public function testPushRev()
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
                throw new UnexpectedValueException('I expected the stack to overflow');
            } catch (OverflowException $e) { }
        }

        // test pushElement

        public function testPushElement()
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

        public function testReduce()
        {
            $stack = new Stack(Stack::INFINITE_SIZE, Stack::ORDER_LIST);
            $stack->push(1, 2, 3, 4);

            $result = $stack->reduce(array($this, 'clbkAnd'));
            $this->assertEquals($result, 0);

            $result = $stack->reduce(array($this, 'clbkXor'));
            $this->assertEquals($result, 4);

            $result = $stack->reduce(array($this, 'clbkMinus'));
            $this->assertEquals($result, -8);

            $stack->order = Stack::ORDER_STACK;
            $result = $stack->reduce(array($this, 'clbkMinus'));
            $this->assertEquals($result, -2);
        }

        // test replace

        public function testReplace()
        {
            $stack = new Stack();
            $stack->push(1);
            $stack->push(2);
            $stack->push(3);

            $stack->replace(1, 3);

            $this->assertTrue($stack->equals(array(1, 3, 3)));

            try {
                $stack->replace(4, 0);
                throw new UnexpectedValueException('I expected the stack to overflow');
            } catch (OutOfRangeException $e) { }
        }

        // test replaceElements

        public function testReplaceElements()
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

        public function testReverse()
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

        public function testSetName()
        {
            $stack = new Stack(Stack::INFINITE_SIZE, Stack::ORDER_LIST);
            $stack->setName('MyStack');
            $string = $stack->__toString();
            $this->assertTrue(strpos($string, 'MyStack') !== false);
        }

        // test shift

        public function testShift()
        {
            $stack = new Stack();
            $stack->push(1);
            $this->assertEquals($stack->shift(), array(1));
            $this->assertEquals($stack->count(), 0);
        }

        public function testShift2()
        {
            $stack = new Stack();

            $stack->push(1);
            $stack->push(2);

            $this->assertEquals($stack->shift(), array(1));
            $this->assertEquals($stack->shift(), array(2));
            $this->assertEquals($stack->count(), 0);
        }

        public function testShift3()
        {
            $stack = new Stack();

            $stack->push(1);
            $stack->push(2);

            $this->assertEquals($stack->shift(2), array(1, 2));
            $this->assertEquals($stack->count(), 0);

            try {
                $stack->shift();
                throw new UnexpectedValueException('I expected the stack to underflow');
            } catch (UnderflowException $e) { }
            $this->assertEquals($stack->count(), 0);
        }

        // test shuffle

        public function testShuffle()
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

        public function testSlice()
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

        public function testSplice()
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

        public function testSplice2()
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

        public function testSort()
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

        public function testSort2()
        {
            $stack = new Stack();
            $stack->push(array('duck'));
            $stack->push(array('monkey'));
            $stack->push(array('zebra'));
            $stack->push(array('frog'));

            $stack->sort(array($this, 'clbkCmp'));
            $this->assertEquals($stack->pop(), array('zebra'));
            $this->assertEquals($stack->pop(), array('monkey'));
            $this->assertEquals($stack->pop(), array('frog'));
            $this->assertEquals($stack->pop(), array('duck'));
        }

        // test unique

        public function testUnique()
        {
            $stack = new Stack();
            $stack->push(1, 1, 1);

            $stack->unique();
            $this->assertEquals($stack->pop(), 1);
            $this->assertEquals($stack->getStackSize(), 0);
        }

        // test unshift

        public function testUnshift()
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

        public function testSimple()
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

    class TestSanitizor extends Sanitizor
    {
        // was this hook called?
        public $pre_processing_hook = false;
        public $post_processing_hook = false;
        public $undefined_value_hook = false;
        public $invalid_value_hook = false;
        public $no_default_value_hook = false;

        public function preProcessingHook($identifier, $value)
        {
            $this->pre_processing_hook = true;
            return NULL;
        }

        public function postProcessingHook($identifier, $value)
        {
            $this->post_processing_hook = true;
            return NULL;
        }

        public function invalidValueHook($identifier, $value)
        {
            $this->invalid_value_hook = true;
            return NULL;
        }

        public function undefinedValueHook($identifier)
        {
            $this->undefined_value_hook = true;
            return NULL;
        }

        public function noDefaultValueHook($identifier)
        {
            $this->no_default_value_hook = true;
            return NULL;
        }

        public function getLog()
        {
            return $this->log;
        }
    }

    function changingHook($identifier, $value=NULL, $args=3)
    {
        static $call_nr = 0;

        if ($call_nr % 3 === 0)
        {
            $validity = true;
            $parameter = 42;
        } else if ($call_nr % 3 === 1) {
            $validity = false;
            $parameter = 42;
        } else if ($call_nr % 3 === 2) {
            $identifier .= '2';
            $validity = NULL;
            $parameter = $value;
        }

        $call_nr++;
        switch ($args)
        {
        case 2:
            return array($validity, $parameter);
        case 3:
            return array($identifier, $validity, $parameter);
        }
    }

    function customFilter($value, $parameters=NULL)
    {
        assert($parameters === 42);

        return array(true, ((string)$value).'2');
    }

    class TestPreProcessingHookSanitizor extends TestSanitizor
    {
        public function preProcessingHook($identifier, $value)
        {
            $this->pre_processing_hook = true;
            return changingHook($identifier, $value, 3);
        }
    }
    class TestPostProcessingHookSanitizor extends TestSanitizor
    {
        public function postProcessingHook($identifier, $value)
        {
            $this->post_processing_hook = true;
            return changingHook($identifier, $value, 2);
        }
    }
    class TestInvalidValueHookSanitizor extends TestSanitizor
    {
        public function invalidValueHook($identifier, $value)
        {
            $this->invalid_value_hook = true;
            return changingHook($identifier, $value, 3);
        }
    }
    class TestUndefinedValueHookSanitizor extends TestSanitizor
    {
        public function undefinedValueHook($identifier)
        {
            $this->undefined_value_hook = true;
            return changingHook($identifier, NULL, 3);
        }
    }
    class TestNoDefaultValueHookSanitizor extends TestSanitizor
    {
        public function noDefaultValueHook($identifier)
        {
            $this->no_default_value_hook = true;
            return changingHook($identifier, NULL, 2);
        }
    }

    class SanitizorTesting extends TestingAdapter {

        public function testSimple()
        {
            $sani = new TestSanitizor();
            $sani->addContext(array(
                'name' => 'foobar',
                'counter' => 3
            ));
            $sani->addRule('name', Sanitizor::TYPE_STRING, 'Rasmus');
            $sani->addRule('counter', Sanitizor::TYPE_INTEGER, 0);
            $sani->addFilter('name', 'upper');

            $this->assertEquals($sani->getParameter('name'), 'FOOBAR');
            $this->assertEquals($sani->getParameter('counter'), 3);
        }

        // test Constructor

        public function testConstructorName()
        {
            $sani = new TestSanitizor(array('name' => 'MySanitizor'));
            $this->assertEquals($sani->getName(), 'MySanitizor');
        }

        public function testConstructorLog()
        {
            $notify = new Notifications();
            $sani = new TestSanitizor(array('log' => $notify));
            $this->assertEquals($sani->getLog(), $notify);
        }

        public function testConstructorUDInvalid()
        {
            $sani = new TestSanitizor(array('use_defaults_invalid' => false));
            $this->assertEquals($sani->getUseDefaultsInvalid(), false);
        }

        public function testConstructorUDUndefined()
        {
            $sani = new TestSanitizor(array('use_defaults_undefined' => false));
            $this->assertEquals($sani->getUseDefaultsUndefined(), false);
        }

        // test getInstance

        public function testGetInstance()
        {
            $sani1 = Sanitizor::getInstance();
            $sani2 = Sanitizor::getInstance();
            $sani3 = Sanitizor::getInstance();

            $sani1->setName('HelloWorld1');
            $sani2->setName('HelloWorld2');
            $sani3->setName('HelloWorld3');

            $this->assertEquals($sani1, $sani2, $sani3);
            $this->assertEquals($sani1->getName(), $sani2->getName(),
                                $sani3->getName());

            unset($sani1);

            // only at object creation
            $sani = Sanitizor::getInstance(array('name' => 'MYsanitizor'));
            $this->assertEquals($sani->getName(), 'HelloWorld3');

            unset($sani2);
            unset($sani3);

            // object creation should trigger
            $sani = Sanitizor::getInstance(array('name' => 'MYsanitizor'));
            $this->assertEquals($sani->getName(), 'HelloWorld3');
        }

        // test setUseDefaults

        public function testUseDefaultsInvalid()
        {
            $sani = new TestSanitizor();
            $sani->setUseDefaultsInvalid(false);
            $this->assertEquals($sani->getUseDefaultsInvalid(), false);
            $sani->setUseDefaultsInvalid(true);
            $this->assertEquals($sani->getUseDefaultsInvalid(), true);
        }

        public function testUseDefaultsUndefined()
        {
            $sani = new TestSanitizor();
            $sani->setUseDefaultsUndefined(false);
            $this->assertEquals($sani->getUseDefaultsUndefined(), false);
            $sani->setUseDefaultsUndefined(true);
            $this->assertEquals($sani->getUseDefaultsUndefined(), true);
        }

        // test setName

        public function testName()
        {
            $sani = new TestSanitizor(array('name' => 'MySanitizor'));
            $this->assertEquals($sani->getName(), 'MySanitizor');
            $sani->setName('CustomSanitizor');
            $this->assertEquals($sani->getName(), 'CustomSanitizor');
        }

        // test hooks

        public function testPreProcessingHook()
        {
            $sani = new TestPreProcessingHookSanitizor();

            $this->assertFalse($sani->pre_processing_hook);
            $this->assertFalse($sani->post_processing_hook);
            $this->assertFalse($sani->undefined_value_hook);
            $this->assertFalse($sani->invalid_value_hook);
            $this->assertFalse($sani->no_default_value_hook);

            $sani->addContext(array('mode' => 3));
            $sani->addRule('mode', Sanitizor::TYPE_INTEGER, 43);
            $this->assertEquals($sani->get('mode'), array(true, 42));

            $this->assertTrue($sani->pre_processing_hook);
            $this->assertFalse($sani->post_processing_hook);
            $this->assertFalse($sani->undefined_value_hook);
            $this->assertFalse($sani->invalid_value_hook);
            $this->assertFalse($sani->no_default_value_hook);


            $sani2 = new TestPreProcessingHookSanitizor();

            $this->assertFalse($sani2->pre_processing_hook);
            $this->assertFalse($sani2->post_processing_hook);
            $this->assertFalse($sani2->undefined_value_hook);
            $this->assertFalse($sani2->invalid_value_hook);
            $this->assertFalse($sani2->no_default_value_hook);

            $sani2->addContext(array('mode' => 3));
            $sani2->addRule('mode', Sanitizor::TYPE_INTEGER, 43);
            $this->assertEquals($sani2->get('mode'), array(false, 42));

            $this->assertTrue($sani2->pre_processing_hook);
            $this->assertFalse($sani2->post_processing_hook);
            $this->assertFalse($sani2->undefined_value_hook);
            $this->assertFalse($sani2->invalid_value_hook);
            $this->assertFalse($sani2->no_default_value_hook);


            $sani3 = new TestPreProcessingHookSanitizor();

            $this->assertFalse($sani3->pre_processing_hook);
            $this->assertFalse($sani3->post_processing_hook);
            $this->assertFalse($sani3->undefined_value_hook);
            $this->assertFalse($sani3->invalid_value_hook);
            $this->assertFalse($sani3->no_default_value_hook);

            $sani3->addContext(array('mode' => 3, 'mode2' => 4));
            $sani3->addRule('mode', Sanitizor::TYPE_INTEGER, 43);
            $this->assertEquals($sani3->get('mode'), array(true, 4));

            $this->assertTrue($sani3->pre_processing_hook);
            $this->assertTrue($sani3->post_processing_hook);
            $this->assertFalse($sani3->undefined_value_hook);
            $this->assertFalse($sani3->invalid_value_hook);
            $this->assertFalse($sani3->no_default_value_hook);
        }

        public function testPostProcessingHook()
        {
            $sani = new TestPostProcessingHookSanitizor();

            $this->assertFalse($sani->pre_processing_hook);
            $this->assertFalse($sani->post_processing_hook);
            $this->assertFalse($sani->undefined_value_hook);
            $this->assertFalse($sani->invalid_value_hook);
            $this->assertFalse($sani->no_default_value_hook);

            $sani->addContext(array('mode' => 3));
            $sani->addRule('mode', Sanitizor::TYPE_INTEGER, 43);
            $this->assertEquals($sani->get('mode'), array(true, 42));

            $this->assertTrue($sani->pre_processing_hook);
            $this->assertTrue($sani->post_processing_hook);
            $this->assertFalse($sani->undefined_value_hook);
            $this->assertFalse($sani->invalid_value_hook);
            $this->assertFalse($sani->no_default_value_hook);


            $sani2 = new TestPostProcessingHookSanitizor();

            $this->assertFalse($sani2->pre_processing_hook);
            $this->assertFalse($sani2->post_processing_hook);
            $this->assertFalse($sani2->undefined_value_hook);
            $this->assertFalse($sani2->invalid_value_hook);
            $this->assertFalse($sani2->no_default_value_hook);

            $sani2->addContext(array('mode' => 3));
            $sani2->addRule('mode', Sanitizor::TYPE_INTEGER, 43);
            $this->assertEquals($sani2->get('mode'), array(false, 42));

            $this->assertTrue($sani2->pre_processing_hook);
            $this->assertTrue($sani2->post_processing_hook);
            $this->assertFalse($sani2->undefined_value_hook);
            $this->assertFalse($sani2->invalid_value_hook);
            $this->assertFalse($sani2->no_default_value_hook);


            $sani3 = new TestPostProcessingHookSanitizor();

            $this->assertFalse($sani3->pre_processing_hook);
            $this->assertFalse($sani3->post_processing_hook);
            $this->assertFalse($sani3->undefined_value_hook);
            $this->assertFalse($sani3->invalid_value_hook);
            $this->assertFalse($sani3->no_default_value_hook);

            $sani3->addContext(array('mode' => 3));
            $sani3->addRule('mode', Sanitizor::TYPE_INTEGER, 43);
            $this->assertEquals($sani3->get('mode'), array(true, 3));

            $this->assertTrue($sani3->pre_processing_hook);
            $this->assertTrue($sani3->post_processing_hook);
            $this->assertFalse($sani3->undefined_value_hook);
            $this->assertFalse($sani3->invalid_value_hook);
            $this->assertFalse($sani3->no_default_value_hook);
        }

        public function testPostProcessingHookRuleDoesNotExist()
        {
            $sani = new TestPostProcessingHookSanitizor();

            $this->assertFalse($sani->pre_processing_hook);
            $this->assertFalse($sani->post_processing_hook);
            $this->assertFalse($sani->undefined_value_hook);
            $this->assertFalse($sani->invalid_value_hook);
            $this->assertFalse($sani->no_default_value_hook);

            $sani->addContext(array('mode' => 3));
            $this->assertEquals($sani->get('mode'), array(true, 42));

            $this->assertTrue($sani->pre_processing_hook);
            $this->assertTrue($sani->post_processing_hook);
            $this->assertFalse($sani->undefined_value_hook);
            $this->assertFalse($sani->invalid_value_hook);
            $this->assertFalse($sani->no_default_value_hook);


            $sani2 = new TestPostProcessingHookSanitizor();

            $this->assertFalse($sani2->pre_processing_hook);
            $this->assertFalse($sani2->post_processing_hook);
            $this->assertFalse($sani2->undefined_value_hook);
            $this->assertFalse($sani2->invalid_value_hook);
            $this->assertFalse($sani2->no_default_value_hook);

            $sani2->addContext(array('mode' => 3));
            $this->assertEquals($sani2->get('mode'), array(false, 42));

            $this->assertTrue($sani2->pre_processing_hook);
            $this->assertTrue($sani2->post_processing_hook);
            $this->assertFalse($sani2->undefined_value_hook);
            $this->assertFalse($sani2->invalid_value_hook);
            $this->assertFalse($sani2->no_default_value_hook);


            $sani3 = new TestPostProcessingHookSanitizor();

            $this->assertFalse($sani3->pre_processing_hook);
            $this->assertFalse($sani3->post_processing_hook);
            $this->assertFalse($sani3->undefined_value_hook);
            $this->assertFalse($sani3->invalid_value_hook);
            $this->assertFalse($sani3->no_default_value_hook);

            $sani3->addContext(array('mode' => 3));
            $this->assertEquals($sani3->get('mode'), array(true, 3));

            $this->assertTrue($sani3->pre_processing_hook);
            $this->assertTrue($sani3->post_processing_hook);
            $this->assertFalse($sani3->undefined_value_hook);
            $this->assertFalse($sani3->invalid_value_hook);
            $this->assertFalse($sani3->no_default_value_hook);
        }

        public function testInvalidValueHook()
        {
            $sani = new TestInvalidValueHookSanitizor();

            $this->assertFalse($sani->pre_processing_hook);
            $this->assertFalse($sani->post_processing_hook);
            $this->assertFalse($sani->undefined_value_hook);
            $this->assertFalse($sani->invalid_value_hook);
            $this->assertFalse($sani->no_default_value_hook);

            $sani->addContext(array('mode' => 'foo'));
            $sani->addRule('mode', Sanitizor::TYPE_INTEGER, 30);
            $this->assertEquals($sani->get('mode'), array(true, 42));

            $this->assertTrue($sani->pre_processing_hook);
            $this->assertFalse($sani->post_processing_hook);
            $this->assertFalse($sani->undefined_value_hook);
            $this->assertTrue($sani->invalid_value_hook);
            $this->assertFalse($sani->no_default_value_hook);


            $sani2 = new TestInvalidValueHookSanitizor();

            $this->assertFalse($sani2->pre_processing_hook);
            $this->assertFalse($sani2->post_processing_hook);
            $this->assertFalse($sani2->undefined_value_hook);
            $this->assertFalse($sani2->invalid_value_hook);
            $this->assertFalse($sani2->no_default_value_hook);

            $sani2->addContext(array('mode' => 'foo'));
            $sani2->addRule('mode', Sanitizor::TYPE_INTEGER, 30);
            $this->assertEquals($sani2->get('mode'), array(false, 42));

            $this->assertTrue($sani2->pre_processing_hook);
            $this->assertFalse($sani2->post_processing_hook);
            $this->assertFalse($sani2->undefined_value_hook);
            $this->assertTrue($sani2->invalid_value_hook);
            $this->assertFalse($sani2->no_default_value_hook);


            $sani3 = new TestInvalidValueHookSanitizor();

            $this->assertFalse($sani3->pre_processing_hook);
            $this->assertFalse($sani3->post_processing_hook);
            $this->assertFalse($sani3->undefined_value_hook);
            $this->assertFalse($sani3->invalid_value_hook);
            $this->assertFalse($sani3->no_default_value_hook);

            $sani3->addContext(array('mode' => 'foo', 'mode2' => 20));
            $sani3->addRule('mode', Sanitizor::TYPE_INTEGER, 30);
            $sani3->addRule('mode2', Sanitizor::TYPE_INTEGER, 40);
            $this->assertEquals($sani3->get('mode'), array(false, 40));

            $this->assertTrue($sani3->pre_processing_hook);
            $this->assertFalse($sani3->post_processing_hook);
            $this->assertFalse($sani3->undefined_value_hook);
            $this->assertTrue($sani3->invalid_value_hook);
            $this->assertFalse($sani3->no_default_value_hook);
        }

        public function testUndefinedValueHook()
        {
            $sani = new TestUndefinedValueHookSanitizor();

            $this->assertFalse($sani->pre_processing_hook);
            $this->assertFalse($sani->post_processing_hook);
            $this->assertFalse($sani->undefined_value_hook);
            $this->assertFalse($sani->invalid_value_hook);
            $this->assertFalse($sani->no_default_value_hook);

            $this->assertEquals($sani->get('mode'), array(true, 42));

            $this->assertFalse($sani->pre_processing_hook);
            $this->assertFalse($sani->post_processing_hook);
            $this->assertTrue($sani->undefined_value_hook);
            $this->assertFalse($sani->invalid_value_hook);
            $this->assertFalse($sani->no_default_value_hook);


            $sani2 = new TestUndefinedValueHookSanitizor();

            $this->assertFalse($sani2->pre_processing_hook);
            $this->assertFalse($sani2->post_processing_hook);
            $this->assertFalse($sani2->undefined_value_hook);
            $this->assertFalse($sani2->invalid_value_hook);
            $this->assertFalse($sani2->no_default_value_hook);

            $this->assertEquals($sani2->get('mode'), array(false, 42));

            $this->assertFalse($sani2->pre_processing_hook);
            $this->assertFalse($sani2->post_processing_hook);
            $this->assertTrue($sani2->undefined_value_hook);
            $this->assertFalse($sani2->invalid_value_hook);
            $this->assertFalse($sani2->no_default_value_hook);


            $sani3 = new TestUndefinedValueHookSanitizor();

            $this->assertFalse($sani3->pre_processing_hook);
            $this->assertFalse($sani3->post_processing_hook);
            $this->assertFalse($sani3->undefined_value_hook);
            $this->assertFalse($sani3->invalid_value_hook);
            $this->assertFalse($sani3->no_default_value_hook);

            $this->assertEquals($sani3->get('mode'), array(false, NULL));

            $this->assertFalse($sani3->pre_processing_hook);
            $this->assertFalse($sani3->post_processing_hook);
            $this->assertTrue($sani3->undefined_value_hook);
            $this->assertFalse($sani3->invalid_value_hook);
            $this->assertFalse($sani3->no_default_value_hook);
        }

        public function testNoDefaultValueHook()
        {
            $sani = new TestNoDefaultValueHookSanitizor();

            $this->assertFalse($sani->pre_processing_hook);
            $this->assertFalse($sani->post_processing_hook);
            $this->assertFalse($sani->undefined_value_hook);
            $this->assertFalse($sani->invalid_value_hook);
            $this->assertFalse($sani->no_default_value_hook);

            $sani->addContext(array('mode' => 'PI'));
            $sani->addRule('mode', Sanitizor::TYPE_INTEGER);
            $this->assertEquals($sani->get('mode'), array(true, 42));

            $this->assertTrue($sani->pre_processing_hook);
            $this->assertFalse($sani->post_processing_hook);
            $this->assertFalse($sani->undefined_value_hook);
            $this->assertTrue($sani->invalid_value_hook);
            $this->assertTrue($sani->no_default_value_hook);


            $sani2 = new TestNoDefaultValueHookSanitizor();

            $this->assertFalse($sani2->pre_processing_hook);
            $this->assertFalse($sani2->post_processing_hook);
            $this->assertFalse($sani2->undefined_value_hook);
            $this->assertFalse($sani2->invalid_value_hook);
            $this->assertFalse($sani2->no_default_value_hook);

            $sani2->addContext(array('mode' => 'PI'));
            $sani2->addRule('mode', Sanitizor::TYPE_INTEGER);
            $this->assertEquals($sani2->get('mode'), array(false, 42));

            $this->assertTrue($sani2->pre_processing_hook);
            $this->assertFalse($sani2->post_processing_hook);
            $this->assertFalse($sani2->undefined_value_hook);
            $this->assertTrue($sani2->invalid_value_hook);
            $this->assertTrue($sani2->no_default_value_hook);


            $sani3 = new TestNoDefaultValueHookSanitizor();

            $this->assertFalse($sani3->pre_processing_hook);
            $this->assertFalse($sani3->post_processing_hook);
            $this->assertFalse($sani3->undefined_value_hook);
            $this->assertFalse($sani3->invalid_value_hook);
            $this->assertFalse($sani3->no_default_value_hook);

            $sani3->addContext(array('mode' => 'PI'));
            $sani3->addRule('mode', Sanitizor::TYPE_INTEGER);
            $this->assertEquals($sani3->get('mode'), array(false, 0));

            $this->assertTrue($sani3->pre_processing_hook);
            $this->assertFalse($sani3->post_processing_hook);
            $this->assertFalse($sani3->undefined_value_hook);
            $this->assertTrue($sani3->invalid_value_hook);
            $this->assertTrue($sani3->no_default_value_hook);
        }

        public function testNoDefaultValueHookNotInAnyContext()
        {
            $sani = new TestNoDefaultValueHookSanitizor();

            $this->assertFalse($sani->pre_processing_hook);
            $this->assertFalse($sani->post_processing_hook);
            $this->assertFalse($sani->undefined_value_hook);
            $this->assertFalse($sani->invalid_value_hook);
            $this->assertFalse($sani->no_default_value_hook);

            $sani->addRule('mode', Sanitizor::TYPE_INTEGER);
            $this->assertEquals($sani->get('mode'), array(true, 42));

            $this->assertFalse($sani->pre_processing_hook);
            $this->assertFalse($sani->post_processing_hook);
            $this->assertTrue($sani->undefined_value_hook);
            $this->assertFalse($sani->invalid_value_hook);
            $this->assertTrue($sani->no_default_value_hook);


            $sani2 = new TestNoDefaultValueHookSanitizor();

            $this->assertFalse($sani2->pre_processing_hook);
            $this->assertFalse($sani2->post_processing_hook);
            $this->assertFalse($sani2->undefined_value_hook);
            $this->assertFalse($sani2->invalid_value_hook);
            $this->assertFalse($sani2->no_default_value_hook);

            $sani2->addRule('mode', Sanitizor::TYPE_INTEGER);
            $this->assertEquals($sani2->get('mode'), array(false, 42));

            $this->assertFalse($sani2->pre_processing_hook);
            $this->assertFalse($sani2->post_processing_hook);
            $this->assertTrue($sani2->undefined_value_hook);
            $this->assertFalse($sani2->invalid_value_hook);
            $this->assertTrue($sani2->no_default_value_hook);


            $sani3 = new TestNoDefaultValueHookSanitizor();

            $this->assertFalse($sani3->pre_processing_hook);
            $this->assertFalse($sani3->post_processing_hook);
            $this->assertFalse($sani3->undefined_value_hook);
            $this->assertFalse($sani3->invalid_value_hook);
            $this->assertFalse($sani3->no_default_value_hook);

            $sani3->addRule('mode', Sanitizor::TYPE_INTEGER);
            $this->assertEquals($sani3->get('mode'), array(false, 0));

            $this->assertFalse($sani3->pre_processing_hook);
            $this->assertFalse($sani3->post_processing_hook);
            $this->assertTrue($sani3->undefined_value_hook);
            $this->assertFalse($sani3->invalid_value_hook);
            $this->assertTrue($sani3->no_default_value_hook);
        }

        // test dump

        public function testDump()
        {
            $sani = new TestSanitizor();
            $sani->setUseDefaultsInvalid(false);
            $sani->addContext(array(3 => array(5, 6), 'foo' => 'bar'));
            $sani->addFilter('foo', 'upper', array(3.141592, 42));

            echo $sani; //->dump('text');
            echo $sani->dump('html');
        }

        // test addContext
        
        public function testContextDefaults()
        {
            $sani = new TestSanitizor();
            // $sani should use the superglobals as default contexts
            $_GET['parameterrrr'] = 3;
            $this->assertEquals($sani->getParameter('parameterrrr'), 3);
        }

        public function testAddContext()
        {
            $sani = new TestSanitizor();
            $sani->clearContexts();
            $sani->addContext(array('mode' => '0x1234'));
            $sani->addRule('mode', Sanitizor::TYPE_HEX, 0xCAFE);
            $this->assertEquals($sani->getParameter('mode'), 0x1234);
        }

        public function testAddContext2()
        {
            $sani = new TestSanitizor();
            $sani->clearContexts();
            $sani->addContext(array('mode' => '0x1234'));
            $sani->addContext(array('mode' => '0x2345'));
            $sani->addRule('mode', Sanitizor::TYPE_HEX, 0xCAFE);
            $this->assertEquals($sani->getParameter('mode'), 0x1234);
        }

        public function testAddContext3()
        {
            $sani = new TestSanitizor();
            $sani->clearContexts();
            $sani->addContext(array());
            $sani->addContext(array());
            $sani->addRule('mode', Sanitizor::TYPE_HEX, 0xCAFE);
            $this->assertEquals($sani->getParameter('mode'), 0xCAFE);
        }

        public function testAddContext4()
        {
            $sani = new TestSanitizor();
            $sani->clearContexts();
            $sani->addContext(array('mode' => '0x1234'), 0);
            $sani->addContext(array('mode' => '0x2345'), 1);
            $sani->addRule('mode', Sanitizor::TYPE_HEX, 0xCAFE);
            $this->assertEquals($sani->getParameter('mode'), 0x1234);
        }

        public function testAddContext5()
        {
            $sani = new TestSanitizor();
            $sani->clearContexts();
            $sani->addContext(array('mode' => 0x1234), 0, true);
            $sani->addContext(array('mode' => 0x2345), 0, true);
            $sani->addRule('mode', Sanitizor::TYPE_HEX, 0xCAFE);
            $this->assertEquals($sani->getParameter('mode'), 0x2345);
        }

        public function testAddContext6()
        {
            $sani = new TestSanitizor();
            $sani->clearContexts();
            $sani->addContext(array('mode' => 0x1234), 0, true);
            $sani->addContext(array('mode' => 0x2345), 0, false);
            $sani->addRule('mode', Sanitizor::TYPE_HEX, 0xCAFE);
            $this->assertEquals($sani->getParameter('mode'), 0x1234);
        }

        public function testAddContext7()
        {
            $sani = new TestSanitizor();
            $sani->clearContexts();
            $sani->addContext(array('mode' => 0x1234), NULL, true);
            $sani->addContext(array('mode' => 0x2345), NULL, true);
            $sani->addRule('mode', Sanitizor::TYPE_HEX, 0xCAFE);
            $this->assertEquals($sani->getParameter('mode'), 0x2345);
        }

        // test removeContext

        public function testRemoveContext()
        {
            $sani = new TestSanitizor();
            $sani->clearContexts();
            $sani->addContext(array('mode' => 0x1234), 0);
            $sani->addContext(array('mode' => 0x2345), 1)->removeContext(0);
            $sani->addRule('mode', Sanitizor::TYPE_HEX, 0xCAFE);
            $this->assertEquals($sani->getParameter('mode'), 0x2345);
        }

        public function testRemoveContext2()
        {
            $sani = new TestSanitizor();
            $sani->clearContexts();
            $sani->addContext(array('mode' => 0x1234), 0, true);
            $sani->addContext(array('mode' => 0x2345), 0, true);
            $sani->removeContext(0);
            $sani->addContext(array('mode' => '0x4567'), 3);
            $sani->addRule('mode', Sanitizor::TYPE_HEX, 0xCAFE);
            $this->assertEquals($sani->getParameter('mode'), 0x4567);
            $sani->addContext(array('mode' => '0x5678'), 5);
            $sani->removeContext(3);
            $this->assertEquals($sani->getParameter('mode'), 0x5678);
        }

        // test clearContext

        public function testClearContexts()
        {
            $sani = new TestSanitizor();
            $sani->clearContexts();
            $sani->addContext(array('mode' => '0x1234'));
            $sani->addContext(array('mode' => '0x2345'));
            $sani->clearContexts();
            $sani->addContext(array('mode' => '0x3456'), 'zoobar');
            $sani->addRule('mode', Sanitizor::TYPE_HEX, 0xCAFE);
            $this->assertEquals($sani->getParameter('mode'), 0x3456);
        }

        // test addFilter

        public function testAddFilter()
        {
            $sani = new TestSanitizor();
            $sani->clearContexts();
            $sani->addContext(array('int' => 4, 'float' => 3.141592,
                'string' => 'fOo '));
            $sani->addFilter('int', Sanitizor::FILTER_BETWEEN, array(array(0, 1)));
            $this->assertEquals($sani->get('int'), array(false, 4));

            $sani->addFilter('float', 'member', array(array(1.3, 3.141592)));
            $this->assertEquals($sani->get('float'), array(true, 3.141592));

            $sani->addFilter('string', 'trim,lower');
            $this->assertEquals($sani->get('string'), array(true, 'foo'));
        }

        public function testAddFilter2()
        {
            $sani = new TestSanitizor();
            $sani->clearContexts();
            $sani->addContext(array('string' => 'fOo ', 'string2' => 'foo Bar',
                'string3' => 'Foo bar', 'string4' => 'Foo bar'));

            $sani->addFilter('string', 'upper,MAXLENGTH', array(NULL, 2));
            $this->assertEquals($sani->get('string'), array(true, 'FO'));

            $sani->addFilter('string2', 'titlecase');
            $this->assertEquals($sani->get('string2'), array(true, 'Foo Bar'));

            $sani->addFilter('string3', 'camelcase');
            $this->assertEquals($sani->get('string3'), array(true, 'FooBar'));

            // test correct order of evaluation
            $sani->addFilter('string4', 'maxlength,camelcase', array(5, NULL));
            $this->assertEquals($sani->get('string4'), array(true, 'FooB'));
        }

        // test registerFilter

        public function testRegisterFilter()
        {
            $sani = new TestSanitizor();
            $sani->clearContexts();
            $sani->addContext(array('string' => 'fOo '));
            $sani->registerFilter('custom_function', 'customFilter');
            $sani->addFilter('string', 'trim,custom_function', array(NULL, 42));
            $this->assertEquals($sani->get('string'), array(true, 'fOo2'));
        }

        // test removeFilter

        public function testRemoveFilter()
        {
            $sani = new TestSanitizor();
            $sani->clearContexts();
            $sani->addContext(array('string' => 'foobar '));
            $sani->addFilter('string', 'trim,lower', array(NULL, 42));
            $sani->removeFilters('string');
            $this->assertEquals($sani->get('string'), array(true, 'foobar '));
        }

        // test clearFilters

        public function testClearFilters()
        {
            $sani = new TestSanitizor();
            $sani->clearContexts();
            $sani->addContext(array('string' => 'foobar ', 'string2' => 'Baz'));
            $sani->addFilter('string', 'trim,lower', array(NULL, 42));
            $sani->addFilter('string2', 'lower');
            $sani->clearFilters();
            $this->assertEquals($sani->get('string'), array(true, 'foobar '));
            $this->assertEquals($sani->get('string2'), array(true, 'Baz'));
        }

        // general tests for various types and filters

        public function testTypes()
        {
            $sani = new TestSanitizor();
            $sani->clearContexts();

            $sani->addContext(array('null' => 'null', 'binary' => '12',
                'bool' => 'true', 'lbool' => 4, 'char' => 'c',
                'alnum' => 'foob4r', 'alpha' => 'Text', 'print' => ' ',
                'whitespace' => " \t\n"));
            $sani->addFilter('alnum', 'trim,upper');

            $sani->addRule('null', Sanitizor::TYPE_NULL);
            $sani->addRule('binary', Sanitizor::TYPE_BINARY);
            $sani->addRule('bool', Sanitizor::TYPE_BOOL);
            $sani->addRule('lbool', Sanitizor::TYPE_LOOSE_BOOL);
            $sani->addRule('char', Sanitizor::TYPE_CHAR);
            $sani->addRule('alnum', Sanitizor::TYPE_ALNUM);
            $sani->addRule('alpha', Sanitizor::TYPE_ALPHA);
            $sani->addRule('print', Sanitizor::TYPE_PRINT);
            $sani->addRule('whitespace', Sanitizor::TYPE_WHITESPACE);

            $this->assertEquals($sani->get('null'), array(true, NULL));
            $this->assertEquals($sani->get('binary'), array(false, 0));
            $this->assertEquals($sani->get('bool'), array(true, true));
            $this->assertEquals($sani->get('lbool'), array(true, true));
            $this->assertEquals($sani->get('char'), array(true, 'c'));
            $this->assertEquals($sani->get('alnum'), array(true, 'FOOB4R'));
            $this->assertEquals($sani->get('alpha'), array(true, 'Text'));
            $this->assertEquals($sani->get('print'), array(true, ' '));
            $this->assertEquals($sani->get('whitespace'), array(true, " \t\n"));
        }
    }

    $test = new FunctionsTesting();
    $test->run();

    $test = new StackTesting();
    $test->run();

    $test = new NotificationsTesting();
    $test->run();

    $test = new SanitizorTesting();
    $test->run();
?>
