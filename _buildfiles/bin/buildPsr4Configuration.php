<?php

declare(strict_types=1);

final class Psr4ConfigurationInstaller
{
    private const COMPOSER_VENDOR_NAME = 'artemeon';

    private const DS = \DIRECTORY_SEPARATOR;

    private function getMetadataForModule(string $modulePath): array
    {
        $metadataFile = $modulePath . self::DS . 'metadata.xml';

        if (!\is_file($metadataFile)) {
            throw new \RuntimeException('no metadata configuration found');
        }

        $document = new \DOMDocument();
        $document->load($metadataFile);
        $xpath = new \DOMXPath($document);

        $title = $xpath->evaluate('string(/package/title/text())');
        $version = $xpath->evaluate('string(/package/version/text())');
        $requiredModules = [];

        foreach ($xpath->query('/package/requiredModules/module') as $module) {
            $name = $xpath->evaluate('string(./@name)', $module);
            $version = $xpath->evaluate('string(./@version)', $module);

            $requiredModules[$name] = $version;
        }

        return [
            'title' => $title,
            'version' => $version,
            'requiredModules' => $requiredModules,
        ];
    }

    private function getNamespaceForModule(string $modulePath): string
    {
        $moduleRoot = \preg_replace('#' . self::DS . '.*#', '', $modulePath);
        $moduleName = \preg_replace('#.*' . self::DS . 'module_#', '', $modulePath);

        $rootNamespace = $moduleRoot === 'core' ? 'Kajona' : 'AGP';

        return $rootNamespace . '\\' . \ucfirst($moduleName);
    }

    private function isClassTokenInPhpFile(string $filePath): bool
    {
        $tokens = \token_get_all(\file_get_contents($filePath));

        foreach ($tokens as $index => $token) {
            if ($token[0] === \T_CLASS && $tokens[$index - 1][0] !== \T_DOUBLE_COLON) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param string $path
     * @return iterable|\SplFileInfo[]
     */
    private function findDirectoriesContainingPhpClasses(string $path): iterable
    {
        $directoryIterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($path, RecursiveDirectoryIterator::SKIP_DOTS),
            RecursiveIteratorIterator::CHILD_FIRST
        );

        foreach ($directoryIterator as $directory) {
            /** @var \SplFileInfo $directory */
            if (!$directory->isDir()) {
                continue;
            }
            if (\glob($directory->getRealPath() . self::DS . '*.php', \GLOB_NOSORT) === []) {
                continue;
            }
            foreach (new DirectoryIterator($directory->getRealPath()) as $file) {
                if (!$file->isFile() || $file->getExtension() !== 'php') {
                    continue;
                }
                if ($this->isClassTokenInPhpFile($file->getRealPath())) {
                    yield $directory;
                }
            }
        }
    }

    private function getNamespaceForModuleDirectory(string $moduleNamespace, string $relativeDirectoryPath): string
    {
        return $moduleNamespace . '\\' . \str_replace(self::DS, '\\', \ucwords($relativeDirectoryPath, self::DS));
    }

    private function getAutoloadNamespacesForModule(string $modulePath): array
    {
        $autoloadNamespaces = [];
        $moduleNamespace = $this->getNamespaceForModule($modulePath);

        foreach ($this->findDirectoriesContainingPhpClasses($modulePath) as $directory) {
            $relativePath = \preg_replace('#.*' . self::DS . 'module_\w+' . self::DS . '#',
                '',
                $directory->getRealPath()
            );
            $autoloadNamespaces[$this->getNamespaceForModuleDirectory($moduleNamespace,
                $relativePath
            ) . '\\'] = $relativePath . self::DS;
        }

        if ($autoloadNamespaces !== []) {
            $autoloadNamespaces[$moduleNamespace . '\\'] = '';
        }

        return $autoloadNamespaces;
    }

    private function updateComposerConfigurationForModule(
        string $modulePath,
        string $moduleIdentifier,
        array $requiredModules,
        array $autoloadNamespaces
    ): void
    {
        $composerFile = $modulePath . '/composer.json';
        $composerConfiguration = [];

        if (\is_file($composerFile)) {
            $composerConfiguration = \json_decode(\file_get_contents($composerFile), true);
        }

        $composerConfiguration = \array_merge(
            [
                'name' => $moduleIdentifier,
                'require' => $requiredModules,
                'autoload' => [
                    'psr-4' => $autoloadNamespaces,
                ],
            ],
            $composerConfiguration
        );

        if ($composerConfiguration['require'] === []) {
            unset($composerConfiguration['require']);
        }
        if ($composerConfiguration['autoload']['psr-4'] === []) {
            unset($composerConfiguration['autoload']);
        }

        \file_put_contents(
            $composerFile,
            \json_encode($composerConfiguration, \JSON_PRETTY_PRINT | \JSON_UNESCAPED_SLASHES) . "\n"
        );
    }

    private function installForModule(string $modulePath): void
    {
        $metadata = $this->getMetadataForModule($modulePath);
        $moduleIdentifier = self::COMPOSER_VENDOR_NAME . '/' . $metadata['title'];
        $requiredModules = \array_column(
            \array_map(
                static function ($name, $version): array {
                    return [self::COMPOSER_VENDOR_NAME . '/' . $name, '~' . $version];
                },
                array_keys($metadata['requiredModules']),
                $metadata['requiredModules']
            ),
            1,
            0
        );
        $autoloadNamespaces = $this->getAutoloadNamespacesForModule($modulePath);

        $this->updateComposerConfigurationForModule($modulePath,
            $moduleIdentifier,
            $requiredModules,
            $autoloadNamespaces
        );
    }

    private function getModulePaths(string $rootPath): iterable
    {
        $modulePaths = \glob($rootPath . self::DS . 'core*' . self::DS . 'module_*', \GLOB_ONLYDIR);

        return \array_map(
            static function (string $modulePath) use ($rootPath): string {
                return \substr($modulePath, \strlen($rootPath) + 1);
            },
            $modulePaths
        );
    }

    public function installForAllModules(): void
    {
        foreach ($this->getModulePaths(__DIR__ . self::DS . '..' . self::DS . '..' . self::DS . '..') as $modulePath) {
            $this->installForModule($modulePath);
        }
    }
}

(new Psr4ConfigurationInstaller())->installForAllModules();
