<?php

arch('no debugging calls are left in the package')
    ->expect(['dd', 'dump', 'ray', 'var_dump', 'print_r'])
    ->each->not->toBeUsed();

arch('enums are backed by strings')
    ->expect('Saade\Facehash\Enums')
    ->toBeStringBackedEnums();
