<?php

namespace cetver\LanguageUrlManager\tests;

use cetver\LanguageUrlManager\UrlManager;
use Codeception\Test\Unit;
use yii\base\InvalidConfigException;
use yii\helpers\ArrayHelper;
use yii\web\Application;
use yii\web\Request;

class UrlManagerTest extends Unit
{
    /**
     * @var \cetver\LanguageUrlManager\tests\UnitTester
     */
    protected $tester;

    public function testInit()
    {
        $this->tester->expectException(
            new InvalidConfigException(
                'The "enablePrettyUrl" property must be set to "true"'
            ),
            function () {
                $this->getUrlManager([
                    'enablePrettyUrl' => false,
                ]);
            }
        );

        $this->tester->expectException(
            new InvalidConfigException(
                'The "languages" property must be an array or callable function that returns an array'
            ),
            function () {
                $this->getUrlManager([
                    'languages' => null,
                ]);
            }
        );

        $languages = ['en', 'ru'];
        $urlManager = $this->getUrlManager([
            'languages' => function () use ($languages) {
                return $languages;
            },
        ]);
        $this->tester->assertSame($languages, $urlManager->languages);
    }

    public function testParseRequestAsPath()
    {
        $urlManager = $this->getUrlManager();
        $this->tester->assertSame(['site/index', []], $urlManager->parseRequest($this->getRequest()));

        $request = $this->getRequest(['pathInfo' => 'en/site/index']);
        $this->tester->assertSame(['site/index', []], $urlManager->parseRequest($request));
        $this->tester->assertSame('en', $request->getQueryParam($urlManager->queryParam));

        $request = $this->getRequest(['pathInfo' => 'ru/site/index']);
        $this->tester->assertSame(['site/index', []], $urlManager->parseRequest($request));
        $this->tester->assertSame('ru', $request->getQueryParam($urlManager->queryParam));

        $request = $this->getRequest(['pathInfo' => 'de/site/index']);
        $this->tester->assertSame(['de/site/index', []], $urlManager->parseRequest($request));
        $this->tester->assertSame(null, $request->getQueryParam($urlManager->queryParam));

        $urlManager = $this->getUrlManager([
            'blacklist' => [
                '/^site.*$/',
            ],
        ]);
        $request = $this->getRequest(['pathInfo' => 'en/site/index']);
        $this->tester->assertSame(['en/site/index', []], $urlManager->parseRequest($request));
        $this->tester->assertSame(null, $request->getQueryParam($urlManager->queryParam));
    }

    public function testParseRequestAsSubdomain()
    {
        $urlManager = $this->getUrlManager([
            'existsLanguageSubdomain' => true,
        ]);
        $this->tester->assertSame(['site/index', []], $urlManager->parseRequest($this->getRequest()));

        $request = $this->getRequest([
            'hostInfo' => 'http://en.example.com',
            'pathInfo' => 'site/index',
        ]);
        $this->tester->assertSame(['site/index', []], $urlManager->parseRequest($request));
        $this->tester->assertSame('en', $request->getQueryParam($urlManager->queryParam));

        $request = $this->getRequest([
            'hostInfo' => 'http://ru.example.com',
            'pathInfo' => 'site/index',
        ]);
        $this->tester->assertSame(['site/index', []], $urlManager->parseRequest($request));
        $this->tester->assertSame('ru', $request->getQueryParam($urlManager->queryParam));

        $request = $this->getRequest([
            'hostInfo' => 'http://de.example.com',
            'pathInfo' => 'site/index',
        ]);
        $this->tester->assertSame(['site/index', []], $urlManager->parseRequest($request));
        $this->tester->assertSame(null, $request->getQueryParam($urlManager->queryParam));

        $urlManager = $this->getUrlManager([
            'existsLanguageSubdomain' => true,
            'blacklist' => [
                '/^site.*$/',
            ],
        ]);
        $request = $this->getRequest([
            'hostInfo' => 'http://en.example.com',
            'pathInfo' => 'site/index',
        ]);
        $this->tester->assertSame(false, $urlManager->parseRequest($request));
    }

    public function testCreateUrlAsPath()
    {
        $urlManager = $this->getUrlManager();
        $request = $this->getRequest();
        $this->mockWebApplication($urlManager, $request);
        $this->tester->assertSame('/site/index', $urlManager->createUrl(['site/index']));

        $this->tester->assertSame(
            '/en/site/index',
            $urlManager->createUrl(['site/index', $urlManager->queryParam => 'en'])
        );

        $this->tester->assertSame(
            '/ru/site/index',
            $urlManager->createUrl(['site/index', $urlManager->queryParam => 'ru'])
        );

        $this->tester->assertSame(
            '/site/index',
            $urlManager->createUrl(['site/index', $urlManager->queryParam => 'de'])
        );

        $request->setQueryParams([$urlManager->queryParam => 'en']);
        $this->tester->assertSame('/en/site/index', $urlManager->createUrl(['site/index']));

        $request->setQueryParams([$urlManager->queryParam => 'ru']);
        $this->tester->assertSame('/ru/site/index', $urlManager->createUrl(['site/index']));

        $request->setQueryParams([$urlManager->queryParam => 'de']);
        $this->tester->assertSame('/site/index', $urlManager->createUrl(['site/index']));

        $urlManager = $this->getUrlManager();
        $request = $this->getRequest();
        $request->setBaseUrl('/admin');
        $this->mockWebApplication($urlManager, $request);
        $this->tester->assertSame(
            '/admin/en/site/index',
            $urlManager->createUrl(['site/index', $urlManager->queryParam => 'en'])
        );

        $this->tester->assertSame(
            '/admin/ru/site/index',
            $urlManager->createUrl(['site/index', $urlManager->queryParam => 'ru'])
        );

        $this->tester->assertSame(
            '/admin/site/index',
            $urlManager->createUrl(['site/index', $urlManager->queryParam => 'de'])
        );

        $request->setQueryParams([$urlManager->queryParam => 'en']);
        $this->tester->assertSame('/admin/en/site/index', $urlManager->createUrl(['site/index']));

        $request->setQueryParams([$urlManager->queryParam => 'ru']);
        $this->tester->assertSame('/admin/ru/site/index', $urlManager->createUrl(['site/index']));

        $request->setQueryParams([$urlManager->queryParam => 'de']);
        $this->tester->assertSame('/admin/site/index', $urlManager->createUrl(['site/index']));

        $urlManager = $this->getUrlManager();
        $request = $this->getRequest();
        $request->setBaseUrl('/site/');
        $this->mockWebApplication($urlManager, $request);

        $request->setQueryParams([$urlManager->queryParam => 'en']);
        $this->tester->assertSame('/site/en/site/index', $urlManager->createUrl(['site/index']));

        $urlManager = $this->getUrlManager();
        $urlManager->addRules([
            'главная' => 'site/index'
        ]);
        $request = $this->getRequest();
        $request->setBaseUrl('/админ');
        $this->mockWebApplication($urlManager, $request);

        $request->setQueryParams([$urlManager->queryParam => 'ru']);
        $this->tester->assertSame('/админ/ru/главная', $urlManager->createUrl(['site/index']));
    }

    public function testCreateUrlAsSubdomain()
    {
        $urlManager = $this->getUrlManager([
            'existsLanguageSubdomain' => true,
        ]);
        $request = $this->getRequest();
        $this->mockWebApplication($urlManager, $request);

        $this->tester->assertSame(
            'http://en.example.com/site/index',
            $urlManager->createUrl(['site/index', $urlManager->queryParam => 'en'])
        );

        $this->tester->assertSame(
            'http://ru.example.com/site/index',
            $urlManager->createUrl(['site/index', $urlManager->queryParam => 'ru'])
        );

        $this->tester->assertSame(
            '/site/index',
            $urlManager->createUrl(['site/index', $urlManager->queryParam => 'de'])
        );

        $request->setHostInfo('http://en.example.com');
        $this->tester->assertSame(
            'http://ru.example.com/site/index',
            $urlManager->createUrl(['site/index', $urlManager->queryParam => 'ru'])
        );

        $request->setHostInfo('http://en.it.example.com');
        $this->tester->assertSame(
            'http://ru.it.example.com/site/index',
            $urlManager->createUrl(['site/index', $urlManager->queryParam => 'ru'])
        );

        $_SERVER['HTTPS'] = 'on';
        $request->setHostInfo('http://en.it.example.com');
        $this->tester->assertSame(
            'https://ru.it.example.com/site/index',
            $urlManager->createUrl(['site/index', $urlManager->queryParam => 'ru'])
        );
    }

    protected function mockWebApplication(UrlManager $urlManager, Request $request, $config = [])
    {
        new Application(ArrayHelper::merge(
            [
                'id' => 'test-app',
                'basePath' => __DIR__,
                'components' => [
                    'urlManager' => $urlManager,
                    'request' => $request,
                ],
            ],
            $config
        ));
    }

    protected function getUrlManager($config = [])
    {
        return new UrlManager(ArrayHelper::merge(
            [
                'languages' => ['en', 'ru'],
                'enablePrettyUrl' => true,
                'showScriptName' => false,
            ],
            $config
        ));
    }

    protected function getRequest($config = [])
    {
        return new Request(ArrayHelper::merge(
            [
                'hostInfo' => 'http://www.example.com',
                'pathInfo' => 'site/index',
                'scriptUrl' => '',
            ],
            $config
        ));
    }
}