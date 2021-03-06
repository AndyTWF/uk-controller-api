<?php
namespace App\Http\Controllers;

use App\BaseApiTestCase;
use App\Models\Version\Version;
use App\Providers\AuthServiceProvider;
use TestingUtils\Traits\WithSeedUsers;

class VersionControllerTest extends BaseApiTestCase
{
    const CREATION_DATE_FIRST = '2017-12-02T00:00:00.000000Z';
    const CREATION_DATE_SECOND = '2017-12-03T00:00:00.000000Z';
    const CREATION_DATE_THIRD = '2017-12-04T00:00:00.000000Z';

    use WithSeedUsers;

    protected static $tokenScope = [
        AuthServiceProvider::SCOPE_USER,
        AuthServiceProvider::SCOPE_VERSION_ADMIN,
    ];

    public function testItConstructs()
    {
        $this->assertInstanceOf(VersionController::class, $this->app->make(VersionController::class));
    }

    public function testGetVersionStatusDoesNotAcceptPost()
    {
        $this->makeAuthenticatedApiRequest(self::METHOD_POST, 'version/1.0.0/status')
            ->assertStatus(405);
    }

    public function testGetVersionStatusFailsIfVersionInvalid()
    {
        $this->makeAuthenticatedApiRequest(self::METHOD_GET, 'version/2.1.2^^^^/status')
            ->assertStatus(404);
    }

    public function testGetVersionStatusResponseValid()
    {
        $this->makeAuthenticatedApiRequest(self::METHOD_GET, 'version/2.0.1/status')
            ->assertJson(
                [
                    'update_available' => false,
                    'version_disabled' => false,
                ]
            )->assertStatus(200);
    }

    public function testItSetsUserVersionInformation()
    {
        $this->makeAuthenticatedApiRequest(self::METHOD_GET, 'version/2.0.1/status')
            ->assertJson(
                [
                    'update_available' => false,
                    'version_disabled' => false,
                ]
            )->assertStatus(200);
        $this->assertEquals(3, $this->activeUser()->last_version);
    }

    public function testGetAllVersionsFailsWithoutVersionAdminScope()
    {
        $this->regenerateAccessToken([], static::$tokenUser);
        $this->makeAuthenticatedApiRequest(self::METHOD_GET, 'version')
            ->assertStatus(403);
    }

    public function testItFindsAllVersions()
    {
        $this->makeAuthenticatedApiRequest(self::METHOD_GET, 'version')
            ->assertJson(
                [
                    [
                        'id' => 1,
                        'version' => '1.0.0',
                        'allowed' => false,
                        'created_at' => self::CREATION_DATE_FIRST,
                        'updated_at' => self::CREATION_DATE_SECOND,
                    ],
                    [
                        'id' => 2,
                        'version' => '2.0.0',
                        'allowed' => true,
                        'created_at' => self::CREATION_DATE_SECOND,
                        'updated_at' => null,
                    ],
                    [
                        'id' => 3,
                        'version' => '2.0.1',
                        'allowed' => true,
                        'created_at' => self::CREATION_DATE_THIRD,
                        'updated_at' => null,
                    ],
                ]
            )->assertStatus(200);
    }

    public function testCreateUpdateVersionFailsWithoutVersionAdminScope()
    {
        $this->regenerateAccessToken([], static::$tokenUser);
        $this->makeAuthenticatedApiRequest(self::METHOD_PUT, 'version/9.6.0', [])
            ->assertStatus(403);
    }

    public function testCreateUpdateVersionFailsIfMissingAllowable()
    {
        $this->makeAuthenticatedApiRequest(
            self::METHOD_PUT,
            'version/9.0.1',
            []
        )->assertStatus(400);
    }

    public function testCreateUpdateVersionReturnsCreatedOnNewVersion()
    {
        $this->makeAuthenticatedApiRequest(
            self::METHOD_PUT,
            'version/9.5.0',
            [
                'allowed' => true,
            ]
        )->assertStatus(201);
    }

    public function testCreateUpdateVersionReturnsUpdatedOnUpdatedVersion()
    {
        $this->makeAuthenticatedApiRequest(
            self::METHOD_PUT,
            'version/2.0.0',
            [
                'allowed' => false,
            ]
        )->assertStatus(204);
    }

    public function testCreateUpdateVersionReturnsUpdateModifiesVersion()
    {
        $this->makeAuthenticatedApiRequest(
            self::METHOD_PUT,
            'version/2.0.0',
            [
                'allowed' => false,
            ]
        );

        $this->assertFalse(Version::find(2)->allowed);
    }

    public function testGetVersionVersionFailsWithoutVersionAdminScope()
    {
        $this->regenerateAccessToken([], static::$tokenUser);
        $this->makeAuthenticatedApiRequest(self::METHOD_GET, 'version/9.1.0', [])
            ->assertStatus(403);
    }


    public function testItFindsAVersion()
    {
        $this->makeAuthenticatedApiRequest(self::METHOD_GET, 'version/1.0.0')
            ->assertJson(
                [
                    'id' => 1,
                    'version' => '1.0.0',
                    'allowed' => false,
                    'created_at' => self::CREATION_DATE_FIRST,
                    'updated_at' => self::CREATION_DATE_SECOND,
                ]
            )->assertStatus(200);
    }

    public function testItReturnsNotFoundIfVersionNotFound()
    {
        $this->makeAuthenticatedApiRequest(self::METHOD_GET, 'version/9.0.0')->assertStatus(404);
    }
}
