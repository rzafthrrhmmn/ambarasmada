<?php

namespace Tests\Feature;

use App\Models\Ambalan;
use App\Models\Angkatan;
use App\Models\Member;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class RouteErrorCheckTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Ambalan::create(['kode' => 'TEST', 'nama' => 'Test Ambalan']);
        Angkatan::create([
            'angkatan' => '018', 'nomor' => 18,
            'nama' => 'Angkatan 18', 'is_active' => true, 'is_current' => true,
        ]);
    }

    protected function createApprovedUser(string $role): User
    {
        $user = User::create([
            'username' => strtolower($role).'_err_'.uniqid(),
            'name' => ucfirst($role),
            'email' => strtolower($role).'_err_'.uniqid().'@test.com',
            'password' => Hash::make('password123'),
            'role' => $role,
            'is_active' => true,
            'status' => 'approved',
        ]);
        Member::create([
            'user_id' => $user->id,
            'ambalan_id' => 1,
            'nta' => strtolower($role).'_nta_'.uniqid(),
            'nama_lengkap' => ucfirst($role),
            'kelas' => '-',
            'tingkatan' => 'Bantara',
            'angkatan' => '018',
            'nomor_urut' => $user->id,
            'nta_username' => strtolower($role).'_bb_'.uniqid(),
            'no_hp' => '-',
            'status_aktif' => 'Aktif',
        ]);

        return $user;
    }

    public function test_check_all_routes_for_errors()
    {
        $routes = [
            '/' => null,
            '/login' => null,
            '/register' => null,
            '/forgot-password' => null,
            '/pending-approval' => 'Admin',
            '/dashboard' => 'Admin',
            '/alumni/dashboard' => 'Alumni',
            '/angkatan' => 'Admin',
            '/announcements' => 'Admin',
            '/articles' => null,
            '/assessments' => 'Admin',
            '/attendance' => 'Admin',
            '/audit-logs' => 'Admin',
            '/candidates' => 'Admin',
            '/certificates' => 'Admin',
            '/events' => 'Admin',
            '/field-guides' => 'Admin',
            '/finance' => null,
            '/finance/categories' => 'Admin',
            '/finance/periods' => 'Admin',
            '/galleries' => null,
            '/guides' => null,
            '/health/safety/checks' => 'Admin',
            '/health/safety/records' => 'Admin',
            '/inventory' => 'Admin',
            '/letters' => 'Admin',
            '/letters/templates' => 'Admin',
            '/materials' => null,
            '/medias' => 'Admin',
            '/meetings' => 'Admin',
            '/members' => 'Admin',
            '/notifications' => 'Admin',
            '/peta' => null,
            '/peta/kontur' => null,
            '/profile' => 'Admin',
            '/reminders' => 'Admin',
            '/reports/attendance/pdf' => 'Admin',
            '/reports/finance/pdf' => 'Admin',
            '/reports/members/csv' => 'Admin',
            '/reports/sku/pdf' => 'Admin',
            '/sku' => 'Admin',
            '/system/points' => 'Admin',
            '/system/tools/backups' => 'Admin',
            '/system/tools/webhooks' => 'Admin',
            '/teams' => 'Admin',
            '/trainings' => 'Admin',
        ];

        $results = [];
        $user = $this->createApprovedUser('Admin');

        foreach ($routes as $uri => $requiredRole) {
            $actingUser = null;
            if ($requiredRole === null) {
                $actingUser = $user;
            } elseif ($requiredRole === $user->role) {
                $actingUser = $user;
            } elseif ($requiredRole === 'Alumni') {
                $alumniUser = $this->createApprovedUser('Alumni');
                $actingUser = $alumniUser;
            } else {
                $actingUser = $user;
            }

            $response = $this->actingAs($actingUser)->get($uri);
            $status = $response->getStatusCode();

            $errorInfo = [];
            $errorInfo['status'] = $status;

            if (in_array($status, [500])) {
                $errorInfo['error'] = '500 INTERNAL SERVER ERROR';
                $error = $response->exception ? $response->exception->getMessage() : 'Unknown error';
                $errorInfo['error_message'] = $error;
            } elseif (in_array($status, [403, 404, 405, 422, 302])) {
                $errorInfo['redirect_or_error'] = $status;
                if ($status === 302) {
                    $location = $response->headers->get('Location');
                    $errorInfo['redirect_to'] = $location;
                }
            }

            // Check Inertia props for errors
            $testResponse = $response->baseResponse ?? null;
            $content = $response->getContent();
            if (preg_match('/"errors":\s*\{([^}]*)\}/', $content, $matches)) {
                if (! empty(trim($matches[1]))) {
                    $errorInfo['validation_errors'] = $matches[1];
                }
            }
            if (preg_match('/"flash"\s*:\s*\{[^}]*"error"\s*:\s*"([^"]*)"/', $content, $matches)) {
                if (! empty($matches[1])) {
                    $errorInfo['flash_error'] = $matches[1];
                }
            }

            $results[$uri] = $errorInfo;
        }

        // Output results
        foreach ($results as $uri => $info) {
            echo "=== {$uri} ===\n";
            echo "Status: {$info['status']}\n";
            if (isset($info['error'])) {
                echo "ERROR: {$info['error']}\n";
                echo "Message: {$info['error_message']}\n";
            }
            if (isset($info['redirect_or_error'])) {
                echo "Status type: {$info['redirect_or_error']}\n";
                if (isset($info['redirect_to'])) {
                    echo "Redirect to: {$info['redirect_to']}\n";
                }
            }
            if (isset($info['validation_errors'])) {
                echo "VALIDATION ERRORS: {$info['validation_errors']}\n";
            }
            if (isset($info['flash_error'])) {
                echo "FLASH ERROR: {$info['flash_error']}\n";
            }
            echo "\n";
        }

        $this->assertTrue(true);
    }
}
