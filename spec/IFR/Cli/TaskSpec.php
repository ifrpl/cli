<?php

namespace spec\IFR\Cli;

use PhpSpec\ObjectBehavior;
use Prophecy\Argument;

class TaskSpec extends ObjectBehavior
{
    function it_is_initializable()
    {
        $this->shouldHaveType('IFR\Cli\Task');
    }
}
