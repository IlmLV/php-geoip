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
        self::assertSame('🇩🇪', Flags::emoji('DE'));
        self::assertSame('🇦🇶', Flags::emoji('AQ'));
    }

    public function testEmojiIsTwoRegionalIndicatorCodepoints(): void
    {
        $emoji = Flags::emoji('LV');
        // Two regional-indicator symbols, each 4 bytes in UTF-8.
        self::assertSame(2, mb_strlen($emoji, 'UTF-8'));
        self::assertSame(8, strlen($emoji));
    }

    public function testEmojiRejectsTooLongCode(): void
    {
        $this->expectException(InvalidArgumentException::class);
        Flags::emoji('LVA');
    }

    public function testEmojiRejectsTooShortCode(): void
    {
        $this->expectException(InvalidArgumentException::class);
        Flags::emoji('L');
    }
}
