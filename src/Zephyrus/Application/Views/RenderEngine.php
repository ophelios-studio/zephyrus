<?php namespace Zephyrus\Application\Views;

interface RenderEngine
{
    public function renderFromFile(string $page, array $args = []): void;
}
