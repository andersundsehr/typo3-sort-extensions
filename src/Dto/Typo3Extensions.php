<?php

declare(strict_types=1);

namespace AUS\Typo3SortPackages\Dto;

final class Typo3Extensions
{
    /**
     * @param array<string> $remoteExtensions
     * @param array<string> $localExtensions
     */
    public function __construct(public array $remoteExtensions, public array $localExtensions)
    {
    }
}
