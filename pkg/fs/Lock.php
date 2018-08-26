<?php

declare(strict_types=1);

namespace Enqueue\Fs;

interface Lock
{
    /**
     * Returns the control If the look has been obtained
     * If not, should throw CannotObtainLockException exception.
     *
     * @param FsDestination $destination
     *
     * @throws CannotObtainLockException if look could not be obtained
     */
    public function lock(FsDestination $destination);

    /**
     * @param FsDestination $destination
     */
    public function release(FsDestination $destination);

    public function releaseAll();
}
