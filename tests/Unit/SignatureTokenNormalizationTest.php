<?php

namespace Tests\Unit;

use App\Services\Api\Ynov\SignatureService;
use PHPUnit\Framework\TestCase;

class SignatureTokenNormalizationTest extends TestCase
{
    public function test_it_normalizes_token_from_full_url_with_query_string_and_trailing_slash(): void
    {
        $this->assertSame(
            'abc123token',
            SignatureService::normalizeToken('https://example.com/signature/widget/abc123token?document_url=https%3A%2F%2Fcdn.example.com%2Fdoc.pdf')
        );
    }

    public function test_it_trims_and_cleans_raw_token_values(): void
    {
        $this->assertSame('xyz789', SignatureService::normalizeToken(' /signature/widget/xyz789/#section '));
    }
}
