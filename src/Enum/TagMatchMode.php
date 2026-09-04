<?php

declare(strict_types=1);

namespace App\Enum;

enum TagMatchMode: string
{
  case Any = 'any';
  case All = 'all';
}
