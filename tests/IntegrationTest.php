<?php

namespace Kint\Test;

use Exception;
use Kint\Kint;
use Kint\Object\BasicObject;
use Kint\Object\BlobObject;
use Kint\Parser\Parser;
use Kint\Parser\ProxyPlugin;
use Kint\Renderer\TextRenderer;
use PHPUnit_Framework_AssertionFailedError;
use PHPUnit_Framework_Exception;

class IntegrationTest extends KintTestCase
{
    /**
     * @covers \d
     * @covers \s
     * @covers \Kint\Kint::dump
     */
    public function testBasicDumps()
    {
        $testdata = array(
            1234,
            (object) array('abc' => 'def'),
            1234.5678,
            'Good news everyone! I\'ve got some bad news!',
            null,
        );

        $testdata[] = &$testdata;

        $array_structure = array(
            '0', 'integer', '1234',
            '1', 'stdClass', '1',
            'public', 'abc', 'string', '3', 'def',
            '2', 'double', '1234.5678',
            '3', 'string', '43', 'Good news everyone! I\'ve got some bad news!',
            '4', 'null',
        );

        Kint::$return = true;
        Kint::$cli_detection = false;
        Kint::$display_called_from = false;

        Kint::$enabled_mode = Kint::MODE_RICH;
        $richbase = d($testdata);

        $this->assertLike(
            array_merge(
                $array_structure,
                array('&amp;array', '6'),
                $array_structure,
                array('&amp;array', 'Recursion')
            ),
            $richbase
        );

        Kint::$enabled_mode = true;
        $this->assertSame($richbase, d($testdata));
        $this->assertSame($richbase, Kint::dump($testdata));

        Kint::$enabled_mode = Kint::MODE_PLAIN;
        $plainbase = d($testdata);

        $this->assertLike(
            array_merge(
                $array_structure,
                array('&amp;array', '6'),
                $array_structure,
                array('&amp;array', 'RECURSION')
            ),
            $plainbase
        );

        $this->assertSame($plainbase, Kint::dump($testdata));

        Kint::$enabled_mode = true;
        $this->assertSame($plainbase, s($testdata));

        Kint::$enabled_mode = Kint::MODE_CLI;
        $clibase = d($testdata);

        $this->assertLike(
            array_merge(
                $array_structure,
                array('&array', '6'),
                $array_structure,
                array('&array', 'RECURSION')
            ),
            $clibase
        );

        $this->assertSame($clibase, Kint::dump($testdata));

        Kint::$enabled_mode = true;
        Kint::$cli_detection = true;
        $this->assertSame($clibase, d($testdata));
        $this->assertSame($clibase, s($testdata));
        Kint::$cli_detection = false;

        Kint::$enabled_mode = Kint::MODE_TEXT;
        $textbase = d($testdata);

        $this->assertLike(
            array_merge(
                $array_structure,
                array('&array', '6'),
                $array_structure,
                array('&array', 'RECURSION')
            ),
            $textbase
        );

        $this->assertSame($textbase, Kint::dump($testdata));

        Kint::$return = false;
        Kint::$enabled_mode = true;
        ob_start();
        ~d($testdata);
        $this->assertSame($textbase, ob_get_clean());

        Kint::$enabled_mode = false;
        $this->assertSame(0, d($testdata));
        $this->assertSame(0, s($testdata));
    }

    /**
     * @covers \Kint\Kint::dump
     */
    public function testDumpBadMode()
    {
        Kint::$return = true;
        Kint::$cli_detection = false;
        Kint::$display_called_from = false;
        Kint::$enabled_mode = Kint::MODE_PLAIN;
        TextRenderer::$decorations = false;

        $d1 = Kint::dump(1234);

        Kint::$enabled_mode = 'This is not a real mode';
        $d2 = Kint::dump(1234);

        $this->assertEquals($d1, $d2);
    }

    /**
     * @covers \Kint\Kint::dump
     */
    public function testFlushModifier()
    {
        Kint::$return = true;
        Kint::$cli_detection = false;
        Kint::$display_called_from = false;
        Kint::$enabled_mode = Kint::MODE_TEXT;
        TextRenderer::$decorations = false;

        $base_level = ob_get_level();
        ob_start();
        $this->assertSame($base_level + 1, ob_get_level());
        Kint::dump(1234);
        $this->assertSame($base_level + 1, ob_get_level());
        // Please leave the ! modifier in place, to prevent errors using unary - on a returned string
        -!Kint::dump(1234);
        $this->assertSame(0, ob_get_level());

        while ($base_level > ob_get_level()) {
            ob_start();
        }
        $this->assertSame($base_level, ob_get_level());
    }

    /**
     * @covers \Kint\Kint::dump
     */
    public function testExpandModifier()
    {
        Kint::$return = true;
        Kint::$cli_detection = false;
        Kint::$display_called_from = false;
        Kint::$enabled_mode = Kint::MODE_RICH;

        $value = array('a' => array(1, 2, 3), 'b' => 'c');

        $d1 = Kint::dump($value);

        Kint::$return = false;
        ob_start();
        !Kint::dump($value);
        $d2 = ob_get_clean();

        $this->assertNotEquals($d1, $d2);

        $d3 = str_replace(' kint-show', '', $d2);
        $this->assertEquals($d1, $d3);
    }

    /**
     * @covers \Kint\Kint::dump
     */
    public function testTextModifier()
    {
        Kint::$return = false;
        Kint::$cli_detection = false;
        Kint::$display_called_from = false;
        Kint::$enabled_mode = Kint::MODE_RICH;

        $value = array('a' => array(1, 2, 3), 'b' => 'c');

        ob_start();
        ~Kint::dump($value);
        $d1 = ob_get_clean();

        Kint::$enabled_mode = Kint::MODE_TEXT;
        Kint::$return = true;
        $d2 = Kint::dump($value);

        $this->assertEquals($d1, $d2);
    }

    /**
     * @covers \Kint\Kint::dump
     */
    public function testDeepModifier()
    {
        Kint::$return = false;
        Kint::$cli_detection = false;
        Kint::$display_called_from = false;
        Kint::$max_depth = 1;
        Kint::$enabled_mode = Kint::MODE_TEXT;

        $value = array('a' => array(1, 2, 3), 'b' => 'c');

        ob_start();
        +Kint::dump($value);
        $d1 = ob_get_clean();

        Kint::$return = true;
        $d2 = Kint::dump($value);

        $this->assertNotEquals($d1, $d2);

        Kint::$max_depth = 0;
        $d2 = Kint::dump($value);

        $this->assertEquals($d1, $d2);
    }

    /**
     * @covers \Kint\Kint::dump
     */
    public function testReturnModifier()
    {
        Kint::$return = false;
        Kint::$cli_detection = false;
        Kint::$display_called_from = false;
        Kint::$enabled_mode = Kint::MODE_TEXT;

        $value = array('a' => array(1, 2, 3), 'b' => 'c');

        ob_start();
        $d1 = @Kint::dump($value);
        $out = ob_get_clean();

        Kint::$return = true;
        $d2 = Kint::dump($value);

        $this->assertEquals($d1, $d2);
        $this->assertEmpty($out);
    }

    /**
     * @covers \Kint\Kint::dump
     * @covers \Kint\Kint::trace
     */
    public function testTrace()
    {
        Kint::$return = true;
        Kint::$cli_detection = false;
        Kint::$display_called_from = false;
        Kint::$enabled_mode = Kint::MODE_TEXT;
        Kint::$max_depth = 3;
        TextRenderer::$decorations = false;

        $bt = debug_backtrace(true);
        $biggerbt = $bt;
        array_unshift($biggerbt, array(
            'class' => 'Kint\\Kint',
            'file' => __FILE__,
        ));

        $d1 = Kint::dump($bt);
        $d2 = Kint::trace($bt);

        $this->assertEquals($d1, $d2);

        $d2 = Kint::dump(1);
        $biggerbt[0]['line'] = __LINE__ - 1;
        $biggerbt[0]['function'] = 'dump';
        $d1 = preg_replace('/^\$biggerbt/', 'Kint\\Kint::dump(1)', Kint::dump($biggerbt));

        $this->assertEquals($d1, $d2);

        $d2 = Kint::trace();
        $biggerbt[0]['line'] = __LINE__ - 1;
        $biggerbt[0]['function'] = 'trace';
        $d1 = preg_replace('/^\$biggerbt/', 'Kint\\Kint::trace()', Kint::dump($biggerbt));

        $this->assertEquals($d1, $d2);
    }

    /**
     * @covers \Kint\Kint::dump
     * @covers \Kint\Kint::trace
     */
    public function testToplevelTrace()
    {
        Kint::$return = true;
        Kint::$cli_detection = false;
        Kint::$display_called_from = false;
        Kint::$enabled_mode = Kint::MODE_TEXT;
        TextRenderer::$decorations = false;

        $bt = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS);
        $firstframe = end($bt);

        if (isset($firstframe['class'])) {
            Kint::$aliases[] = array($firstframe['class'], $firstframe['function']);
        } else {
            Kint::$aliases[] = $firstframe['function'];
        }

        $d1 = Kint::dump(1);
        $d2 = Kint::trace();

        $d1 = explode("\n", $d1);
        array_shift($d1);
        $d1 = implode("\n", $d1);

        $d2 = explode("\n", $d2);
        array_shift($d2);
        $d2 = implode("\n", $d2);

        $this->assertEquals($d1, $d2);

        $this->assertLike(
            array(
                'Debug Backtrace (1):',
                Kint::shortenPath($firstframe['file']).':'.$firstframe['line'],
            ),
            $d1
        );
    }

    /**
     * @covers \Kint\Kint::dump
     */
    public function testDumpNothing()
    {
        Kint::$return = true;
        Kint::$cli_detection = false;
        Kint::$display_called_from = false;
        Kint::$enabled_mode = Kint::MODE_TEXT;
        TextRenderer::$decorations = false;

        $d = Kint::dump();
        $this->assertSame("No argument\n", $d);
    }

    /**
     * @covers \Kint\Kint::dump
     */
    public function testNoParamNames()
    {
        Kint::$return = true;
        Kint::$cli_detection = false;
        Kint::$display_called_from = false;
        Kint::$enabled_mode = Kint::MODE_RICH;
        TextRenderer::$decorations = false;

        $values = array(array(1), array(2), array(3));

        $d = call_user_func_array('Kint::dump', $values);
        $this->assertLike(
            array(
                '$0[0]',
                '$1[0]',
                '$2[0]',
            ),
            $d
        );
    }

    /**
     * @covers \Kint\Kint::dumpArray
     */
    public function testDumpArray()
    {
        Kint::$return = true;
        Kint::$cli_detection = false;
        Kint::$display_called_from = false;
        Kint::$enabled_mode = Kint::MODE_TEXT;
        TextRenderer::$decorations = false;

        $a = 1;
        $b = 2;
        $c = 3;

        $d1 = Kint::dump($a, $b, $c);
        $d2 = Kint::dumpArray(
            array(1, 2, 3),
            array(
                BasicObject::blank('$a'),
                BasicObject::blank('$b'),
                BasicObject::blank('$c'),
            )
        );

        $this->assertEquals($d1, $d2);
    }

    /**
     * @covers \Kint\Kint::dump
     */
    public function testPlugins()
    {
        Kint::$return = true;
        Kint::$cli_detection = false;
        Kint::$display_called_from = false;
        Kint::$enabled_mode = Kint::MODE_TEXT;
        TextRenderer::$decorations = false;

        $p1_triggered = false;
        $p1 = new ProxyPlugin(
            array('resource'),
            Parser::TRIGGER_SUCCESS,
            function () use (&$p1_triggered) {
                $p1_triggered = true;
            }
        );

        $value = fopen(__FILE__, 'r');

        try {
            Kint::$plugins = array();
            $d1 = Kint::dump($value);

            Kint::$plugins = array(
                $p1,
                'Kint\\Parser\\StreamPlugin',
            );
            TextRenderer::$parser_plugin_whitelist = array('Kint\\Parser\\Plugin');

            $this->assertFalse($p1_triggered);

            $d2 = Kint::dump($value);

            fclose($value);
        } catch (Exception $e) {
            fclose($value);

            throw $e;
        }

        $this->assertTrue($p1_triggered);
        $this->assertLike(
            array(
                '$value',
                'stream resource',
                Kint::shortenPath(__FILE__),
            ),
            $d2
        );
        $this->assertNotSame($d1, $d2);
    }

    /**
     * Test this test suite's restore after test.
     *
     * @covers \Kint\Test\KintTestCase::setUp
     * @covers \Kint\Test\KintTestCase::tearDown
     */
    public function testStore()
    {
        Kint::$file_link_format = 'test_store';
        $this->assertEquals('test_store', Kint::$file_link_format);
        BlobObject::$char_encodings[] = 'this_is_not_a_real_encoding';
        $this->assertContains('this_is_not_a_real_encoding', BlobObject::$char_encodings);
    }

    /**
     * @covers \Kint\Test\KintTestCase::setUp
     * @covers \Kint\Test\KintTestCase::tearDown
     */
    public function testRestore()
    {
        $this->assertNotEquals('test_store', Kint::$file_link_format);
        $this->assertNotContains('this_is_not_a_real_encoding', BlobObject::$char_encodings);
    }

    /**
     * @covers \Kint\Test\KintTestCase::assertLike
     */
    public function testLike()
    {
        $this->assertLike(array('a', 'b', 'c'), 'foo a bar baz c buzz');
    }

    /**
     * @covers \Kint\Test\KintTestCase::assertLike
     */
    public function testNotLike()
    {
        try {
            $this->assertLike(array('a', 'b', 'c', 'o'), 'foo a bar baz c buzz');
        } catch (PHPUnit_Framework_AssertionFailedError $e) {
            return;
        }

        self::fail('Failed to mismatch');
    }

    /**
     * @covers \Kint\Test\KintTestCase::assertLike
     */
    public function testLikeNonString()
    {
        try {
            $this->assertLike(array('a', 'b', 'c'), array('a', 'b', 'c'));
        } catch (PHPUnit_Framework_Exception $e) {
            return;
        }

        self::fail('Failed to throw');
    }
}
