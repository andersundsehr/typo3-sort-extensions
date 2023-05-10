<?php

declare(strict_types=1);

namespace AUS\Typo3SortPackages\Dto;

class Typo3Extensions
{
    /**
     * @param array<string> $remoteExtensions
     * @param array<string> $localExtensions
     */
    public function __construct(public readonly array $remoteExtensions, public readonly array $localExtensions)
    {
    }
}
