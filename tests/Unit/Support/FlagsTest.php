<?php

namespace IlmLV\GeoIp\Tests\Unit\Support;

use IlmLV\GeoIp\Support\Flags;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

final class FlagsTest extends TestCase
{
    public function testEmojiFromIsoCode(): void
    {
        self::assertSame('🇱🇻', Flags::emoji('LV'));
        self::assertSame('🇺🇸', Flags::emoji('us')); // case-insensitive
    }

    public function testEmojiRejectsNonTwoLetterCode(): void
    {
        $this->expectException(InvalidArgumentException::class);
        Flags::emoji('LVA');
    }
}
