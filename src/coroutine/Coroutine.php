<?php
/**
 * @link https://github.com/bixuehujin/blink
 * @copyright Copyright (c) 2015 Jin Hu
 * @license the MIT License
 */

namespace blink\coroutine;

use SplStack;
use blink\core\Object;

/**
 * Coroutine represents a user-space "thread" of execution.
 *
 * @method return($value)
 * @method throw($exception)
 *
 * @package blink\coroutine
 */
class Coroutine extends Object
{
    /**
     * @var SplStack
     */
    protected $stack;
    protected $coroutine;

    public function __construct($coroutine, $config = [])
    {
        $this->coroutine = $coroutine;
        $this->stack = new SplStack();

        parent::__construct($config);
    }

    public function run()
    {
        $task = $this->coroutine;

        while (true) {
            if (!$task->valid()) {
                if (!$this->stack->isEmpty()) {
                    $task = $this->stack->pop();
                } else {
                    assert(0);
                }
            }

            $value = $task->current();

            if ($value instanceof \Generator) {
                $this->stack->push($task);
                $task = $value;
                continue;
            } else if ($value instanceof ReturnValue) {
                $task->next();

                if ($task === $this->coroutine) {
                    return $value->value;
                }

                if (!$this->stack->isEmpty()) {
                    $task = $this->stack->pop();
                    $task->send($value->value);
                }

            } else if (is_scalar($value)) {
                $task->send($value);
            } else {
                $task->send(null);
            }
        }
    }
}