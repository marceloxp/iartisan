<?php

namespace Marceloxp\Iartisan\Config;

class ConfigManager
{
    protected string $path;
    protected array $data = [];

    public function __construct()
    {
        $home = getenv('HOME') ?: getenv('USERPROFILE') ?: sys_get_temp_dir();
        $dir = rtrim($home, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . '.iartisan';
        if (!is_dir($dir)) {
            @mkdir($dir, 0700, true);
        }
        $this->path = $dir . DIRECTORY_SEPARATOR . 'config.json';

        if (file_exists($this->path)) {
            $content = file_get_contents($this->path);
            $this->data = json_decode($content, true) ?: [];
        } else {
            $this->data = [];
        }
    }

    public function has(string $key): bool
    {
        return array_key_exists($key, $this->data);
    }

    private function save(): void
    {
        file_put_contents(
            $this->path,
            json_encode($this->data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES)
        );
    }

    public function get(string $key, $default = null)
    {
        return $this->data[$key] ?? $default;
    }

    public function set(string $key, $value): void
    {
        $this->data[$key] = $value;
        file_put_contents($this->path, json_encode($this->data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
    }

    public function remove(string $key): void
    {
        if (isset($this->data[$key])) {
            unset($this->data[$key]);
            $this->save();
        }
    }
}
