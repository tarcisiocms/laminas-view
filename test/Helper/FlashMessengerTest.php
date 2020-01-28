<?php

/**
 * @see       https://github.com/laminas/laminas-view for the canonical source repository
 * @copyright https://github.com/laminas/laminas-view/blob/master/COPYRIGHT.md
 * @license   https://github.com/laminas/laminas-view/blob/master/LICENSE.md New BSD License
 */

namespace LaminasTest\View\Helper;

use PHPUnit_Framework_TestCase as TestCase;
use Laminas\Mvc\Controller\PluginManager;
use Laminas\Mvc\Controller\Plugin\FlashMessenger as PluginFlashMessenger;
use Laminas\ServiceManager\Config;
use Laminas\ServiceManager\ServiceManager;
use Laminas\View\HelperPluginManager;
use Laminas\View\Helper\FlashMessenger;
use LaminasTest\Session\TestAsset\TestManager as SessionManager;

/**
 * Test class for Laminas_View_Helper_Cycle.
 *
 * @category   Laminas
 * @package    Laminas_View
 * @subpackage UnitTests
 * @group      Laminas_View
 * @group      Laminas_View_Helper
 */
class FlashMessengerTest extends TestCase
{
    public function setUp()
    {
        $this->session = new SessionManager();
        $this->helper = new FlashMessenger();
        $this->helper->setSessionManager($this->session);
        $this->plugin = $this->helper->getPluginFlashMessenger();
    }

    public function seedMessages()
    {
        $helper = new FlashMessenger();
        $helper->setSessionManager($this->session);
        $helper->addMessage('foo');
        $helper->addMessage('bar');
        $helper->addInfoMessage('bar-info');
        $helper->addSuccessMessage('bar-success');
        $helper->addErrorMessage('bar-error');
        unset($helper);
    }

    public function testCanAssertPluginClass()
    {
        $this->assertEquals(
            'Laminas\Mvc\Controller\Plugin\FlashMessenger',
            get_class($this->plugin)
        );
        $this->assertEquals(
            'Laminas\Mvc\Controller\Plugin\FlashMessenger',
            get_class($this->helper->getPluginFlashMessenger())
        );
        $this->assertSame(
            $this->plugin,
            $this->helper->getPluginFlashMessenger()
        );
    }

    public function testCanRetrieveMessages()
    {
        $helper = $this->helper;

        $this->assertFalse($helper()->hasMessages());
        $this->assertFalse($helper()->hasInfoMessages());
        $this->assertFalse($helper()->hasSuccessMessages());
        $this->assertFalse($helper()->hasErrorMessages());

        $this->seedMessages();

        $this->assertTrue(count($helper('default')) > 0);
        $this->assertTrue(count($helper('info')) > 0);
        $this->assertTrue(count($helper('success')) > 0);
        $this->assertTrue(count($helper('error')) > 0);

        $this->assertTrue($this->plugin->hasMessages());
        $this->assertTrue($this->plugin->hasInfoMessages());
        $this->assertTrue($this->plugin->hasSuccessMessages());
        $this->assertTrue($this->plugin->hasErrorMessages());
    }

    public function testCanProxyAndRetrieveMessagesFromPluginController()
    {
        $this->assertFalse($this->helper->hasMessages());
        $this->assertFalse($this->helper->hasInfoMessages());
        $this->assertFalse($this->helper->hasSuccessMessages());
        $this->assertFalse($this->helper->hasErrorMessages());

        $this->seedMessages();

        $this->assertTrue($this->helper->hasMessages());
        $this->assertTrue($this->helper->hasInfoMessages());
        $this->assertTrue($this->helper->hasSuccessMessages());
        $this->assertTrue($this->helper->hasErrorMessages());
    }

    public function testCanDisplayListOfMessages()
    {
        $displayInfoAssertion = '';
        $displayInfo = $this->helper->render(PluginFlashMessenger::NAMESPACE_INFO);
        $this->assertEquals($displayInfoAssertion, $displayInfo);

        $this->seedMessages();

        $displayInfoAssertion = '<ul class="info"><li>bar-info</li></ul>';
        $displayInfo = $this->helper->render(PluginFlashMessenger::NAMESPACE_INFO);
        $this->assertEquals($displayInfoAssertion, $displayInfo);
    }

    public function testCanDisplayListOfMessagesByDefaultParameters()
    {
        $helper = $this->helper;
        $this->seedMessages();

        $displayInfoAssertion = '<ul class="default"><li>foo</li><li>bar</li></ul>';
        $displayInfo = $helper()->render();
        $this->assertEquals($displayInfoAssertion, $displayInfo);
    }

    public function testCanDisplayListOfMessagesByInvoke()
    {
        $helper = $this->helper;
        $this->seedMessages();

        $displayInfoAssertion = '<ul class="info"><li>bar-info</li></ul>';
        $displayInfo = $helper()->render(PluginFlashMessenger::NAMESPACE_INFO);
        $this->assertEquals($displayInfoAssertion, $displayInfo);
    }

    public function testCanDisplayListOfMessagesCustomised()
    {
        $this->seedMessages();

        $displayInfoAssertion = '<div class="foo-baz foo-bar"><p>bar-info</p></div>';
        $displayInfo = $this->helper
                ->setMessageOpenFormat('<div%s><p>')
                ->setMessageSeparatorString('</p><p>')
                ->setMessageCloseString('</p></div>')
                ->render(PluginFlashMessenger::NAMESPACE_INFO, array('foo-baz', 'foo-bar'));
        $this->assertEquals($displayInfoAssertion, $displayInfo);
    }

    public function testCanDisplayListOfMessagesCustomisedByConfig()
    {
        $this->seedMessages();

        $config = array(
            'view_helper_config' => array(
                'flashmessenger' => array(
                    'message_open_format' => '<div%s><ul><li>',
                    'message_separator_string' => '</li><li>',
                    'message_close_string' => '</li></ul></div>',
                ),
            ),
        );
        $sm = new ServiceManager();
        $sm->setService('Config', $config);
        $helperPluginManager = new HelperPluginManager(new Config(array(
            'factories' => array(
                'flashmessenger' => 'Laminas\View\Helper\Service\FlashMessengerFactory',
            ),
        )));
        $controllerPluginManager = new PluginManager(new Config(array(
            'invokables' => array(
                'flashmessenger' => 'Laminas\Mvc\Controller\Plugin\FlashMessenger',
            ),
        )));
        $helperPluginManager->setServiceLocator($sm);
        $controllerPluginManager->setServiceLocator($sm);
        $sm->setService('ControllerPluginManager', $controllerPluginManager);
        $helper = $helperPluginManager->get('flashmessenger');

        $displayInfoAssertion = '<div class="info"><ul><li>bar-info</li></ul></div>';
        $displayInfo = $helper->render(PluginFlashMessenger::NAMESPACE_INFO);
        $this->assertEquals($displayInfoAssertion, $displayInfo);
    }

    public function testCanTranslateMessages()
    {
        $mockTranslator = $this->getMock('Laminas\I18n\Translator\Translator');
        $mockTranslator->expects($this->exactly(1))
        ->method('translate')
        ->will($this->returnValue('translated message'));

        $this->helper->setTranslator($mockTranslator);
        $this->assertTrue($this->helper->hasTranslator());

        $this->seedMessages();

        $displayAssertion = '<ul class="info"><li>translated message</li></ul>';
        $display = $this->helper->render(PluginFlashMessenger::NAMESPACE_INFO);
        $this->assertEquals($displayAssertion, $display);
    }
}
