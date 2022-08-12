<?php
    
namespace Tests\Feature;

use App\Models\User;
use Illuminate\Http\Response;
use Tests\TestCase;
use Illuminate\Support\Str;
    
class AuthControllerTest extends TestCase 
{
  public function testSignupCreatesUserSuccessfully()
  {
    $passwd = $this->faker->password;

    $payload = [
      'name'  => "{$this->faker->firstName} {$this->faker->lastName}",
      'email' => $this->faker->email,
      'password' => $passwd,
      'c_password' => $passwd
    ];

    $this->postJson('api/admin/auth/signup', $payload)
      ->assertStatus(Response::HTTP_CREATED)
      ->assertJsonStructure([
        'token',
        'user_id'
      ])
      ->assertJson([
        'name' => $payload['name']
      ]);

    $this->assertDatabaseHas('users', [
      'name'  => $payload['name'],
      'email' => $payload['email'],
    ]);
  }

  public function testSignupWithExistingUser()
  {
    $user = User::create(
      [
        'name'      => "{$this->faker->firstName} {$this->faker->lastName}",
        'email'     => $this->faker->email,
        'password'  => bcrypt('A123456b*'),
      ]
    );

    $passwd = $this->faker->password;
    $payload = [
      'name'  => "{$this->faker->firstName} {$this->faker->lastName}",
      'email' => $user->email,                                                // User already created with this email 
      'password' => $passwd,
      'c_password' => $passwd
    ];

    $this->postJson('api/admin/auth/signup', $payload)
      ->assertStatus(Response::HTTP_UNPROCESSABLE_ENTITY)
      ->assertJsonStructure([
        'errorMessage',
      ])
      ->assertJson([
        'success'   => false,
        'errorCode' => '422',
        'showType'  => 9,
        'data' => [
          'email' => ['The email has already been taken.']
        ]
      ]);
  }

  public function testSignupWithWrongData()
  {
    $payload = [
      'name'  => "Julian76 Smith",                                            // Wrong format
      'email' => 'mail',                                                      // Wrong format
      //'password' => 'A98765432b*',                                          // Missing
      'c_password' => 'A123456b*'                                             // Different to password: c_password                                        
    ];

    $this->postJson('api/admin/auth/signup', $payload)
      ->assertStatus(Response::HTTP_UNPROCESSABLE_ENTITY)
      ->assertJsonStructure([
        'errorMessage',
        'data' => [
          'name',               // Included because does not match with /^[a-zA-Z@áéíóúÁÉÍÓÚñÑ\s]+$/
          'email',              // Included because wrong format
          'password',           // Included because missing
          'c_password'          // Included because does not match 'password'
        ]
      ])
      ->assertJson([
        'success'   => false,
        'errorCode' => '422',
        'showType'  => 9,
      ]);
  }

  public function testLoginSuccessful()
  {
    $user = User::create(
      [
        'name'      => "{$this->faker->firstName} {$this->faker->lastName}",
        'email'     => $this->faker->email,
        'password'  => bcrypt('A123456b*'),
      ]
    );

    $payload = [
      'email'       => $user->email,
      'password'    => 'A123456b*'
    ];

    $response = $this->postJson('api/admin/auth/login', $payload)
      ->assertStatus(Response::HTTP_OK)
      ->assertJsonStructure([
        'token'
      ])
      ->assertJson([
        'name'      => $user->name,
        'user_id'   => $user->id
      ]);

    $token_id = Str::before($response['token'], '|');
    $this->assertDatabaseHas('personal_access_tokens', [
      'id'            => $token_id,
      'tokenable_id'  => $user->id,
    ]);
  }

  public function testLoginWithWrongPassword()
  {
    $user = User::create(
      [
        'name'      => "{$this->faker->firstName} {$this->faker->lastName}",
        'email'     => $this->faker->email,
        'password'  => bcrypt('A123456b*'),
      ]
    );

    $payload = [
      'email'       => $user['email'],
      'password'    => 'A123'               // Wrong password
    ];

    $this->postJson('api/admin/auth/login', $payload)
      ->assertStatus(Response::HTTP_UNAUTHORIZED)
      ->assertExactJson([
        'success'   => false,
        'errorCode' => '401',
        'errorMessage' =>	"Wrong credentials.",
        'showType'  => 9,
      ]);
  }

  public function testLoginWithWrongData()
  {
    $payload = [
      'email' => 'mail',                                                      // Wrong format
      //'password' => 'A98765432b*',                                          // Missing                                     
    ];

    $this->postJson('api/admin/auth/login', $payload)
      ->assertStatus(Response::HTTP_UNPROCESSABLE_ENTITY)
      ->assertJsonStructure([
        'errorMessage',
        'data' => [
          'email',              // Included because wrong format
          'password',           // Included because missing
        ]
      ])
      ->assertJson([
        'success'   => false,
        'errorCode' => '422',
        'showType'  => 9,
      ]);
  }

  public function testGetCurrentUserInformationCorrectly()
  {
    $user =  User::create([
      'name'      => "{$this->faker->firstName} {$this->faker->lastName}",
      'email'     => $this->faker->email,
      'password'  => bcrypt($this->faker->password),
    ]);

    $this->actingAs($user);

    $this->getJson('api/admin/auth/current-user')
      ->assertStatus(Response::HTTP_OK)
      ->assertExactJson([
        'id'    => $user->id,
        'name'  => $user->name,
        'email' => $user->email,
      ]);
  }

  public function testLogoutSuccessful()
  {
    // Use login instead actingAs() to simulate real use of token
    $passwd = $this->faker->password;
    $user =  User::create([
      'name'      => "{$this->faker->firstName} {$this->faker->lastName}",
      'email'     => $this->faker->email,
      'password'  => bcrypt($passwd),
    ]);

    $payload = [
      'email'       => $user->email,
      'password'    => $passwd,
    ];

    // Do login. Previous test on this feature ensures that returned token is stored in 'personal_access_tokens'
    $response = $this->postJson('api/admin/auth/login', $payload);
    $token_id = Str::before($response['token'], '|');
    
    // Do logout
    $this->postJson('api/admin/auth/logout')
      ->assertNoContent();

    // Assert that current token was removed from database
    $this->assertDatabaseMissing('personal_access_tokens', [
      'id'            => $token_id,
      'tokenable_id'  => $user->id,
    ]);
  }

}