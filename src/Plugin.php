<?php

declare(strict_types=1);

namespace AUS\Typo3SortPackages;

use AUS\Typo3SortPackages\Dto\Typo3Extensions;
use Composer\Composer;
use Composer\EventDispatcher\EventSubscriberInterface;
use Composer\InstalledVersions;
use Composer\IO\IOInterface;
use Composer\Json\JsonFile;
use Composer\Json\JsonManipulator;
use Composer\Plugin\PluginInterface;
use Composer\Script\Event;
use RuntimeException;

use function realpath;

final class Plugin implements PluginInterface, EventSubscriberInterface
{
    /**
     * @return string[]
     */
    public static function getSubscribedEvents(): array
    {
        return [
            'pre-autoload-dump' => 'action',
        ];
    }

    public function action(Event $event): void
    {
        $composer = $event->getComposer();
        $rootPackage = $composer->getPackage();
        /** @var array<string> $rootRequiredPackages */
        $rootRequiredPackages = [
            ...array_keys($rootPackage->getRequires()),
            ...array_keys($rootPackage->getDevRequires()),
            ...array_keys($rootPackage->getSuggests()),
        ];

        $typo3Extensions = $this->getTypo3Extensions($rootPackage->getName(), ...$rootRequiredPackages);
        $copyDestination = $composer->getPackage()->getExtra()['andersundsehr/typo3-sort-extensions']['site-package'] ?? null;
        if (!$copyDestination) {
            throw new RuntimeException(
                'you required andersundsehr/typo3-sort-extensions but did not specify extra.andersundsehr/typo3-sort-extensions.site-package in your composer.json'
            );
        }

        $changed = $this->suggestExtensions($copyDestination, ...$typo3Extensions->remoteExtensions);
        foreach ($typo3Extensions->localExtensions as $extension) {
            if ($extension === $copyDestination) {
                continue;
            }

            $changed = $this->suggestExtensions($extension, $copyDestination) || $changed;
        }

        if ($changed) {
            $event->getIO()->write('<info> requirements changed in one of your local extensions.</info>');
            $event->getIO()->write(sprintf('used <info>%s</info> as site package', $copyDestination));
            $event->getIO()->write('Restarting composer command:');
            $event->getIO()->write('');
            $ansi = $event->getIO()->isDecorated() ? ' --ansi' : '';
            passthru('composer update --lock --no-scripts' . $ansi, $resultCode);
            die($resultCode);
        }
    }

    private function getTypo3Extensions(string $rootPackageName, string ...$rootRequiredPackageNames): Typo3Extensions
    {
        $remoteExtensions = [];
        $localExtensions = [];
        foreach (InstalledVersions::getAllRawData() as $rootPackage) {
            if ($rootPackage['root']['name'] !== $rootPackageName) {
                continue;
            }

            foreach ($rootPackage['versions'] as $name => $package) {
                if (!str_starts_with($package['type'] ?? '', 'typo3-cms-')) {
                    // only use typo3 extensions
                    continue;
                }

                if (!in_array($name, $rootRequiredPackageNames, true)) {
                    // only root required packages.
                    continue;
                }

                if (!isset($package['install_path'])) {
                    continue;
                }

                //on composer dump it is an Absolute path with /../ in it.
                //on composer require it is included 2 times.
                // absolute path with /../ in it and
                // relative and starts with ../
                $isAbsolutePath = str_starts_with($package['install_path'], '/');
                if (!$isAbsolutePath) {
                    continue;
                }

                // remote packages are these that are not symlinked:
                if (!is_link($package['install_path'])) {
                    $remoteExtensions[$name] = true;
                    continue;
                }

                //local packages must be in git
                if (!$this->isInGit($package['install_path'])) {
                    continue;
                }

                $localExtensions[$name] = true;
            }
        }

        return new Typo3Extensions(array_keys($remoteExtensions), array_keys($localExtensions));
    }

    private function suggestExtensions(string $copyDestination, string ...$extensionNames): bool
    {
        $composerJsonPath = realpath(InstalledVersions::getInstallPath($copyDestination) . '/composer.json');
        assert(is_string($composerJsonPath), sprintf('cloud not find realpath for %s', $copyDestination));
        $contents = file_get_contents($composerJsonPath);
        assert(is_string($contents), sprintf('cloud not read from %s', $composerJsonPath));
        $manipulator = new JsonManipulator($contents);
        $rawData = JsonFile::parseJson($contents);

        $changed = false;
        foreach ($extensionNames as $extensionName) {
            if (!isset($rawData['suggest'][$extensionName])) {
                $manipulator->addLink('suggest', $extensionName, '*', true);
                $changed = true;
            }
        }

        if (!$changed) {
            return false;
        }

        if (file_put_contents($composerJsonPath, $manipulator->getContents())) {
            return $changed;
        }

        throw new RuntimeException('Unable to write new ' . $composerJsonPath . ' contents.');
    }

    private function isInGit(mixed $path): bool
    {
        return (bool)exec('git ls-files --error-unmatch ' . realpath($path) . '/composer.json 2> /dev/null');
    }

    public function activate(Composer $composer, IOInterface $io): void
    {
    }

    public function deactivate(Composer $composer, IOInterface $io): void
    {
    }

    public function uninstall(Composer $composer, IOInterface $io): void
    {
    }
}
