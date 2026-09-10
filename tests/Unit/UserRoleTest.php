<?php

namespace Tests\Unit;

use App\Models\Api\Ynov\parameter\Role;
use App\Models\Api\Ynov\parameter\User;
use PHPUnit\Framework\TestCase;

class UserRoleTest extends TestCase
{
    public function test_user_has_role_by_code(): void
    {
        $user = new User();
        $user->setRelation('role', new Role([
            'code' => 'gestionnaire_rdv',
            'is_super_admin' => false,
        ]));

        $this->assertTrue($user->hasRole('gestionnaire_rdv'));
        $this->assertFalse($user->hasRole('client'));
    }
}
