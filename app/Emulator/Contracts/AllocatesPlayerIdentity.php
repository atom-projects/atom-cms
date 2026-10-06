<?php

namespace App\Emulator\Contracts;

interface AllocatesPlayerIdentity
{
    /** @return array{id: int} */
    public function allocateIdentity(): array;
}
