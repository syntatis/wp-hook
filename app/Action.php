<?php

declare(strict_types=1);

namespace Syntatis\WPHook;

use Attribute;

#[Attribute(Attribute::TARGET_METHOD | Attribute::TARGET_CLASS | Attribute::IS_REPEATABLE)]
class Action extends Filter
{
}
