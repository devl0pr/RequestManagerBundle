<?php


namespace Devl0pr\RequestManager;

use Symfony\Component\HttpKernel\Bundle\Bundle;

class Devl0prRequestManagerBundle extends Bundle
{
    public function getPath(): string
    {
        return \dirname(__DIR__);
    }
}