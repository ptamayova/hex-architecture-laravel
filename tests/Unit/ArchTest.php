<?php

declare(strict_types=1);

arch()->preset()->php();

// Manually apply strict rules: concrete classes should be final
// Exclude: DomainException (abstract base class), Interfaces (can't be final)
arch('concrete classes should be final')
    ->expect([
        'Src\\Authentication\\Application',
        'Src\\Authentication\\Domain\\Entities',
        'Src\\Authentication\\Domain\\ValueObjects',
        'Src\\Authentication\\Infrastructure',
    ])
    ->toBeFinal();

arch()->preset()->security();

arch('controllers')
    ->expect('App\\Http\\Controllers')
    ->not->toBeUsed();
