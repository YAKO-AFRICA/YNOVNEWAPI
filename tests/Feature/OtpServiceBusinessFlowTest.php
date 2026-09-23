<?php

namespace Tests\Feature;

use App\Models\Api\Ynov\parameter\OtpCode;
use App\Models\Api\Ynov\parameter\User;
use App\Services\Api\Ynov\Auth\OtpService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OtpServiceBusinessFlowTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_generates_and_verifies_an_otp_for_business_operations(): void
    {
        $user = User::create([
            'uuid_user' => '11111111-1111-4111-8111-111111111111',
            'login' => 'user-prestation',
            'email' => 'user@example.com',
            'password' => bcrypt('Password123!'),
            'user_type' => 'client',
            'status' => 'active',
        ]);

        $service = app(OtpService::class);

        $code = $service->generate($user, 'email', 'prestation_request');

        $this->assertNotEmpty($code);
        $this->assertTrue($service->verify($user, $code, 'prestation_request'));
        $this->assertDatabaseHas('otp_codes', [
            'user_uuid' => $user->uuid_user,
            'purpose' => 'prestation_request',
            'is_used' => true,
        ]);
    }

    public function test_it_allows_otp_resend_for_business_operations(): void
    {
        $user = User::create([
            'uuid_user' => '22222222-2222-4222-8222-222222222222',
            'login' => 'user-resend',
            'email' => 'resend@example.com',
            'password' => bcrypt('Password123!'),
            'user_type' => 'client',
            'status' => 'active',
        ]);

        $service = app(OtpService::class);

        $firstCode = $service->generate($user, 'email', 'subscription');
        $firstRecord = OtpCode::where('user_uuid', $user->uuid_user)
            ->where('purpose', 'subscription')
            ->latest('created_at')
            ->first();

        $this->assertNotNull($firstRecord);
        $this->assertSame(0, (int) $firstRecord->resend_count);

        $result = $service->resendOtp($user, 'email', 'subscription', null, null, 5, [
            'email' => $user->email,
        ]);

        $this->assertTrue($result['success']);

        $latestRecord = OtpCode::where('user_uuid', $user->uuid_user)
            ->where('purpose', 'subscription')
            ->latest('created_at')
            ->first();

        $this->assertNotNull($latestRecord);
        $this->assertSame(1, (int) $latestRecord->resend_count);
        $this->assertNotSame($firstCode, $latestRecord->code);
    }
}
