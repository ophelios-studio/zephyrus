<?php

namespace Zephyrus\Application\Views;

interface RenderEngine
{
    public function buildView(string $page): View;

    public function renderFromFile(string $path, array $args = []): void;
}
