<?php namespace Zephyrus\Application\Views;

use RuntimeException;

class PhpEngine implements RenderEngine
{
    public const NAME = 'php';

    public function renderFromFile(string $page, array $args = []): void
    {
        $realPath = realpath(ROOT_DIR . '/app/Views/' . $page . '.php');
        if (!file_exists($realPath) || !is_readable($realPath)) {
            throw new RuntimeException("The specified view file [$page] is not available (not readable or does not exists)");
        }
        foreach ($args as $name => $value) {
            $$name = $value;
        }
        include $realPath;
    }
}
