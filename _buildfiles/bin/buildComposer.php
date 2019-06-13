<?php

declare(strict_types=1);

final class ComposerBuilder {
    private const DS = \DIRECTORY_SEPARATOR;

    private const COMPOSER_REPOSITORY_URL = 'https://buildpackages.kajona.de:5443';
    private const PUBLIC_COMPOSER_PACKAGES = ['artemeon/image', 'artemeon/pdf'];

    /**
     * @param string $rootPath
     * @return string[]
     */
    private function getIncludedModules(string $rootPath): array
    {
        $includedModules = [];

        if (\is_file($rootPath . self::DS . 'project' . self::DS . 'packageconfig.json')) {
            $includedModules = \json_decode(\file_get_contents($rootPath . self::DS . 'project' . self::DS . 'packageconfig.json'), true);
        }

        return $includedModules;
    }

    /**
     * @param string $rootPath
     * @return iterable|array[]
     */
    private function getModuleComposerConfigurations(string $rootPath): iterable
    {
        $includedModules = $this->getIncludedModules($rootPath);

        foreach (new \DirectoryIterator($rootPath) as $rootDirectory) {
            if ($rootDirectory->isDot() || !$rootDirectory->isDir()) {
                continue;
            }
            if (!\preg_match('/^core(?:_[a-z]+)?$/', $rootDirectory->getFilename())) {
                continue;
            }

            $moduleRoot = $rootDirectory->getFilename();

            foreach (new \DirectoryIterator($rootDirectory->getRealPath()) as $moduleDirectory) {
                if (!$moduleDirectory->isDir() || \strpos($moduleDirectory->getFilename(), 'module_') !== 0) {
                    continue;
                }

                $isAnIncludedModule = !isset($includedModules[$moduleRoot]) ||
                    \in_array($moduleDirectory->getFilename(), $includedModules[$moduleRoot], true);

                if ($isAnIncludedModule) {
                    $composerFile = $moduleDirectory->getRealPath() . self::DS . 'composer.json';

                    if (!\is_file($composerFile)) {
                        continue;
                    }

                    $composerConfiguration = \json_decode(\file_get_contents($composerFile), true);

                    if (
                        \is_array(@$composerConfiguration['autoload'])
                        && \array_keys($composerConfiguration['autoload']) !== ['psr-4']
                    ) {
                        throw new \RuntimeException('non psr-4 module composer autoload configuration not supported');
                    }

                    yield $moduleDirectory->getRealPath() => $composerConfiguration;
                }
            }
        }
    }

    private function mergeConfigurations(string $rootPath): void
    {
        if (\is_file($rootPath . self::DS . 'project' . self::DS . 'composer.lock')) {
            return;
        }

        $composerConfiguration = [
            'repositories' => [
                [
                    'type' => 'composer',
                    'url' => self::COMPOSER_REPOSITORY_URL,
                ],
            ],
            'require' => [],
            'autoload' => [
                'psr-4' => [],
            ],
        ];

        foreach ($this->getModuleComposerConfigurations($rootPath) as $modulePath => $moduleComposerConfiguration) {
            if (@\is_array($moduleComposerConfiguration['require'])) {
                $moduleRequirements = $moduleComposerConfiguration['require'];

                foreach ($moduleRequirements as $name => $version) {
                    if (isset($moduleRequirements[$name]) && $moduleRequirements[$name] !== $version) {
                        throw new \RuntimeException(
                            \sprintf('unable to resolve differing requirements %1$s:%2$s vs %1$s:%3$s',
                                $name,
                                $moduleRequirements[$name],
                                $version
                            )
                        );
                    }

                    if (\strpos($name, 'artemeon/') !== 0 || \in_array($name, self::PUBLIC_COMPOSER_PACKAGES, true)) {
                        $composerConfiguration['require'][$name] = $version;
                    }
                }
            }
            if (@\is_array($moduleComposerConfiguration['autoload'])) {
                $autoloadPathPrefix = \substr($modulePath, \strlen(\dirname($modulePath, 2))+1);

                foreach ($moduleComposerConfiguration['autoload']['psr-4'] as $namespace => $path) {
                    $autoloadPath = '../' . \rtrim($autoloadPathPrefix . self::DS . \ltrim($path, self::DS), self::DS) . self::DS;
                    $composerConfiguration['autoload']['psr-4'][$namespace] = $autoloadPath;
                }
            }
        }

        \file_put_contents(
            $rootPath . self::DS . 'project' . self::DS . 'composer.json',
            \json_encode($composerConfiguration, \JSON_PRETTY_PRINT|\JSON_UNESCAPED_SLASHES|\JSON_UNESCAPED_UNICODE)
        );
    }

    private function installPackages(string $projectPath): void
    {
        if (!\is_writable($projectPath)) {
            throw new \RuntimeException(\sprintf('target folder %s is not writable', $projectPath));
        }

        passthru('composer install --no-dev --prefer-dist --optimize-autoloader --working-dir ' . escapeshellarg($projectPath), $exitCode);

        if ($exitCode !== 0) {
            switch ($exitCode) {
                case 127:
                    throw new \RuntimeException(\sprintf('composer was not found. please run "composer install --prefer-dist --working-dir %s" manually', $projectPath));
                case 1:
                    throw new \RuntimeException(\sprintf('composer error. please run "composer install --prefer-dist --working-dir %s" manually', $projectPath));
                default:
                    throw new \RuntimeException('Error exited with a non successful status code');
            }
        }
    }

    public function run(string $rootPath): void
    {
        $this->mergeConfigurations($rootPath);
        $this->installPackages($rootPath . self::DS . 'project');
    }
}

(new ComposerBuilder())->run(__DIR__ . '/../../..');
