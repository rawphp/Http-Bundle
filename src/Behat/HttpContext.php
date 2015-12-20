<?php

namespace RawPHP\HttpBundle\Behat;

use Behat\Behat\Hook\Scope\ScenarioScope;
use Behat\Gherkin\Node\TableNode;
use Behat\Symfony2Extension\Context\KernelAwareContext;
use Behat\Symfony2Extension\Context\KernelDictionary;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Response;
use RawPHP\Http\Factory\IClientFactory;
use RawPHP\Http\Handler\PredictedHandler;
use RawPHP\Http\Util\RequestMap;
use Symfony\Component\Filesystem\Exception\FileNotFoundException;

/**
 * Class HttpContext
 *
 * @package RawPHP\HttpBundle
 */
class HttpContext implements KernelAwareContext
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

        $this->handler->setMatchers([]);

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
}
