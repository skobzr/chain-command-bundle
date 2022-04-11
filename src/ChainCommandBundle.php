<?php

namespace skobzr\ChainCommandBundle;

use Symfony\Component\HttpKernel\Bundle\Bundle;

class ChainCommandBundle extends Bundle
{
    public function getPath(): string
    {
        return \dirname(__DIR__);
    }
}
