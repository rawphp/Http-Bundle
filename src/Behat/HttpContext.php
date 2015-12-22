<?php

namespace RawPHP\HttpBundle\Behat;

use Behat\Behat\Context\Context;
use Behat\Behat\Hook\Scope\ScenarioScope;
use Behat\Gherkin\Node\TableNode;
use Behat\Symfony2Extension\Context\KernelAwareContext;
use Behat\Symfony2Extension\Context\KernelDictionary;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Response;
use InvalidArgumentException;
use Psr\Http\Message\RequestInterface;
use RawPHP\Http\Factory\IClientFactory;
use RawPHP\Http\Handler\PredictedHandler;
use RawPHP\Http\Util\RequestMap;
use Symfony\Component\Filesystem\Exception\FileNotFoundException;

/**
 * Class HttpContext
 *
 * @package RawPHP\HttpBundle
 */
class HttpContext implements Context, KernelAwareContext
{
    use KernelDictionary;

    /** @var  string */
    protected $resourcePath;
    /** @var  IClientFactory */
    protected $clientFactory;
    /** @var  PredictedHandler */
    protected $handler;

    /**
     * @BeforeScenario
     */
    public function init(ScenarioScope $scope)
    {
        $this->resourcePath = $this->getContainer()->getParameter('rawphp_http.resource_dir');

        $this->clientFactory = $this->getContainer()->get('rawphp_http.factory.client');

        $this->handler = new PredictedHandler();

        $this->clientFactory->setHandler($this->handler);
    }

    /**
     * @Given /^there are the following requests and responses:$/
     */
    public function thereAreTheFollowingRequestsAndResponses(TableNode $table)
    {
        foreach ($table->getHash() as $data) {
            $url             = null;
            $body            = null;
            $method          = 'GET';
            $responseContent = '';
            $responseCode    = 200;

            if (!empty($data['url'])) {
                $url = $data['url'];
            }

            if (!empty($data['method'])) {
                $method = $data['method'];
            }

            if (!empty($data['request_body'])) {
                $file = $this->resourcePath . '/' . $data['request_body'];

                if (!file_exists($file)) {
                    throw new FileNotFoundException(sprintf('File "%s" not found', $file));
                }

                $body = file_get_contents($file);
            }

            if (!empty($data['response_body'])) {
                $file = $this->resourcePath . '/' . $data['response_body'];

                if (!file_exists($file)) {
                    throw new FileNotFoundException(sprintf('File "%s" not found', $file));
                }

                $responseContent = file_get_contents($file);
            }

            if (!empty($data['response_code'])) {
                $responseCode = $data['response_code'];
            }

            $request  = new Request($method, $url, [], $body);
            $response = new Response($responseCode, [], $responseContent);

            $map = new RequestMap($request, $response);

            $this->handler->addRequestMap($map);
        }
    }

    /**
     * @Given /^the following matchers exist:$/
     */
    public function theFollowingMatchersExist(TableNode $table)
    {
        foreach ($table->getHash() as $data) {
            $name = $data['name'];
            $class = $data['class'];
            $method = $data['method'];

            if (!class_exists($class)) {
                throw new InvalidArgumentException(sprintf('Class "%s" not found', $class));
            }

            $object = new $class();

            if (!method_exists($object, $method)) {
                throw new InvalidArgumentException(sprintf('Method "%s" not found on "%s"', $method, $class));
            }

            $this->handler->addMatcher(
                $name,
                function (RequestInterface $request, array $maps) use ($object, $method) {
                    return $object->$method($request, $maps);
                }
            );
        };
    }
}
