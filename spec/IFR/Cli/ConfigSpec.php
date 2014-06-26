<?php

namespace spec\IFR\Cli;

use PhpSpec\ObjectBehavior;
use Prophecy\Argument;

class ConfigSpec extends ObjectBehavior
{
    function it_is_initializable()
    {
        $this->shouldHaveType('IFR\Cli\Config');
    }
}
