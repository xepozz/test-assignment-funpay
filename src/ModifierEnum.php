<?php

namespace Xepozz\FunpayTestAssignment\Tests;

enum ModifierEnum: string
{
    case CONDITIONAL_BLOCK = '1';
    case CONDITIONAL_BLOCK_SKIP = '2';
    case ARRAY = '?a';
    case INTEGER = '?d';
    case IDENTIFIERS = '?#';
    case ANY = '?';
}
