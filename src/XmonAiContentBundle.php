<?php

declare(strict_types=1);

namespace Xmon\AiContentBundle;

use Symfony\Component\HttpKernel\Bundle\Bundle;

class XmonAiContentBundle extends Bundle
{
    public function getPath(): string
    {
        return realpath(\dirname(__DIR__)) ?: \dirname(__DIR__);
    }
}
