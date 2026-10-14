<?php

use App\Controllers\AuthController;
use CodeIgniter\HTTP\IncomingRequest;
use CodeIgniter\Test\CIUnitTestCase;

final class LoginValidationTest extends CIUnitTestCase
{
    public function testLoginAcceptsPostWhenRequestDoesNotContainPost(): void
    {
        $this->assertLoginValidation(
            ['usuario' => 'cagurtoc', 'password' => 'Example8'],
            [],
            true,
        );
    }

    public function testOtherInputCannotOverridePostCredentials(): void
    {
        $this->assertLoginValidation(
            ['usuario' => 'cagurtoc', 'password' => 'Example8'],
            ['usuario' => 'invalid!', 'password' => 'x'],
            true,
        );
    }

    public function testInvalidPostIsRejectedEvenWhenRequestIsValid(): void
    {
        $this->assertLoginValidation(
            ['usuario' => 'cagurtoc', 'password' => 'x'],
            ['usuario' => 'cagurtoc', 'password' => 'Example8'],
            false,
        );
    }

    public function testValidationUsesNormalizedUsername(): void
    {
        $data = ['usuario' => ' cagurtoc ', 'password' => 'Example8'];
        $this->assertLoginValidation($data, $data, true);
    }

    private function assertLoginValidation(array $post, array $requestData, bool $expected): void
    {
        service('validation')->reset();
        $request = service('incomingrequest');
        $request->setGlobal('post', $post);
        $request->setGlobal('request', $requestData);
        $controller = new LoginValidationProbe();
        $controller->setRequestForTest($request);

        try {
            $controller->attempt();
            $this->fail('The login must validate input before accessing the database.');
        } catch (LoginValidationStopped $result) {
            $this->assertSame($expected, $result->valid);
        }
    }
}

// Stop at the validation boundary: these tests never query users or change accounts.
final class LoginValidationStopped extends RuntimeException
{
    public function __construct(public bool $valid)
    {
        parent::__construct('Validation completed');
    }
}

final class LoginValidationProbe extends AuthController
{
    public function setRequestForTest(IncomingRequest $request): void
    {
        $this->request = $request;
    }

    protected function validate($rules, array $messages = []): bool
    {
        throw new LoginValidationStopped(parent::validate($rules, $messages));
    }

    protected function validateData(array $data, $rules, array $messages = [], ?string $dbGroup = null): bool
    {
        throw new LoginValidationStopped(parent::validateData($data, $rules, $messages, $dbGroup));
    }
}
