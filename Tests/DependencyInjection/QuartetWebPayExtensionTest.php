<?php


namespace Quartet\Bundle\WebPayBundle\Tests\DependencyInjection;


use Quartet\Bundle\WebPayBundle\DependencyInjection\QuartetWebPayExtension;
use Symfony\Component\DependencyInjection\ContainerBuilder;

class QuartetWebPayExtensionTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var ContainerBuilder
     */
    private $configuration;

    /**
     * @var QuartetWebPayExtension
     */
    private $loader;

    protected function setUp()
    {
        $this->configuration = new ContainerBuilder();
        $this->loader = new QuartetWebPayExtension();
    }

    protected function tearDown()
    {
        $this->configuration = null;
        $this->loader = null;
    }

    /**
     * @test
     * @expectedException \Symfony\Component\Config\Definition\Exception\InvalidConfigurationException
     */
    public function testThrowExceptionUnlessApiSecretSet()
    {
        $config = $this->getEmptyConfig();
        unset($config['api_secret']);
        $this->loader->load(array($config), $this->configuration);
    }

    public function testDefaultConfiguration()
    {
        $config = $this->getEmptyConfig();
        $this->loader->load(array($config), $this->configuration);

        $this->assertParameter('my_api_secret_key', 'quartet_webpay.api_secret');
        $this->assertParameter('my_api_public_key', 'quartet_webpay.api_public');
        $this->assertParameter(null, 'quartet_webpay.api_base');
    }

    /**
     * @test
     */
    public function testOverrideApiBase()
    {
        $config = $this->getEmptyConfig();
        $config['api_base'] = 'http://acme.com/';
        $this->loader->load(array($config), $this->configuration);

        $this->assertParameter('http://acme.com/', 'quartet_webpay.api_base');
    }

    /**
     * @test
     */
    public function testWebPayServiceExists()
    {
        $config = $this->getEmptyConfig();
        $this->loader->load(array($config), $this->configuration);

        $this->assertHasDefinition('quartet_webpay_client');

        $webpay = $this->configuration->get('quartet_webpay_client');

        $this->assertInstanceOf('WebPay\WebPay', $webpay);
    }

    /**
     * @test
     */
    public function testSetAcceptLanguageIfConfigure()
    {
        $config = $this->getEmptyConfig();
        $config['accept_language'] = 'ja';
        $this->loader->load(array($config), $this->configuration);

        $this->assertHasDefinition('quartet_webpay_client');

        $definition = $this->configuration->getDefinition('quartet_webpay_client');
        $methodCalls = $definition->getMethodCalls();
        $this->assertCount(1, $methodCalls);
        $this->assertEquals(array('acceptLanguage', array('ja')), $methodCalls[0]);
    }

    /**
     * @test
     */
    public function testWebPayApiServiceExists()
    {
        $loader = new QuartetWebPayExtension();
        $config = $this->getEmptyConfig();
        $loader->load(array($config), $this->configuration);

        $services = array(
            'customers' => 'WebPay\Api\Customers',
            'account'   => 'WebPay\Api\Account',
            'tokens'    => 'WebPay\Api\Tokens',
            'events'    => 'WebPay\Api\Events',
            'charges'   => 'WebPay\Api\Charges',
        );

        foreach ($services as $id => $class) {
            $this->assertHasDefinition("quartet_webpay.{$id}");
            $api = $this->configuration->get("quartet_webpay.{$id}");
            $this->assertInstanceOf($class, $api);
        }
    }

    /**
     * @param $id
     */
    private function assertHasDefinition($id)
    {
        $this->assertTrue($this->configuration->hasDefinition($id));
    }

    /**
     * @param $value
     * @param $key
     */
    private function assertParameter($value, $key)
    {
        $this->assertSame($value, $this->configuration->getParameter($key));
    }

    /**
     * @return array
     */
    private function getEmptyConfig()
    {
        return array(
            'api_secret'    => 'my_api_secret_key',
            'api_public'    => 'my_api_public_key',
        );
    }
}
