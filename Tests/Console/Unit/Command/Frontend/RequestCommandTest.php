<?php
declare(strict_types=1);
namespace  Helhum\Typo3Console\Tests\Unit\Command\Frontend;

use Helhum\Typo3Console\Command\Frontend\FrontendRequestCommand;
use Nimut\TestingFramework\TestCase\UnitTestCase;

class RequestCommandTest extends UnitTestCase
{
    /**
     * Call protected/private method of a class.
     *
     * @param object &$object    Instantiated object that we will run method on.
     * @param string $methodName Method name to call
     * @param array  $parameters Array of parameters to pass into method.
     *
     * @return mixed Method return.
     */
    public function invokeMethod(&$object, $methodName, array $parameters = [])
    {
        $reflection = new \ReflectionClass(get_class($object));
        $method = $reflection->getMethod($methodName);
        $method->setAccessible(true);

        return $method->invokeArgs($object, $parameters);
    }

    /**
     * @test
     * @dataProvider makeAbsoluteDataProvider
     * @param mixed $expected
     * @param mixed $given
     */
    public function makeAbsoluteTest($expected, $given)
    {
        /** @var FrontendRequestCommand $frontendRequest */
        $frontendRequest = new FrontendRequestCommand();
        $parameter = [ $given ];
        $this->assertEquals($expected, $this->invokeMethod($frontendRequest, 'makeAbsolute', $parameter));
    }

    /**
     * @return array
     */
    public function makeAbsoluteDataProvider()
    {
        return [
            'Relative path to home' => ['http://localhost/', '/'],
            'Relative path to foo without trailing slash' => ['http://localhost/foo', '/foo'],
            'Relative path to foo without leading slash' => ['http://localhost/foo', 'foo'],
            'Relative path to foo with trailing slash' => ['http://localhost/foo/', '/foo/'],
            'Test with HTTP scheme' => ['http://typo3.org/', 'http://typo3.org/'],
            'Test with HTTPS scheme' => ['https://typo3.org/', 'https://typo3.org/'],
            'Test with open scheme' => ['http://typo3.org/', '//typo3.org/'],
            'No scheme but domain' => ['http://localhost/typo3.org', 'typo3.org'],
            'No scheme but domain with trailing slash' => ['http://localhost/typo3.org/', 'typo3.org/'],
            'No scheme but domain with path without trailing slash' => ['http://localhost/typo3.org/foo', 'typo3.org/foo'],
            'No scheme but domain with path and trailing slash' => ['http://localhost/typo3.org/foo/', 'typo3.org/foo/'],
            'Path with query string' => ['http://localhost/foo/?foo=bar&bla=baz', '/foo/?foo=bar&bla=baz'],
        ];
    }
}
