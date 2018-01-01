<?php

namespace cetver\LanguageUrlManager;

use Yii;
use yii\base\InvalidConfigException;
use yii\helpers\ArrayHelper;
use yii\web\Request;

/**
 * Class UrlManager parses and creates URLs containing languages.
 *
 * @package cetver\LanguageUrlManager
 */
class UrlManager extends \yii\web\UrlManager
{
    /**
     * The host separator.
     */
    const SEPARATOR_HOST = '.';
    /**
     * The request path separator.
     */
    const SEPARATOR_PATH = '/';
    /**
     * The "www" domain.
     */
    const DOMAIN_WWW = 'www';
    /**
     * @var array|callable the list of available languages.
     */
    public $languages = [];
    /**
     * @var bool
     * - true: processes the URL like "en.example.com"
     * - false: processes the URL like "example.com/en"
     * NOTE: If this property set to true, the domain containing a language, must be the first on the left side,
     * for example:
     * - en.it.example.com - is valid
     * - it.en.example.com - is invalid
     */
    public $existsLanguageSubdomain = false;
    /**
     * @var array the regular expression patterns list, applied to path info, if there are matches, the request,
     * containing a language, will not be processed.
     * For performance reasons, the blacklist does not applied for URL creation (Take a look at an example).
     * @see \yii\web\Request::getPathInfo()
     * An example:
     * ```php
     * [
     *     '/^api.*$/'
     * ]
     * ```
     * - Requesting the blacklisted URL
     *   - $existsLanguageSubdomain = true
     *     - en.example.com/api (404 Not Found)
     *     - en.example.com/api/create (404 Not Found)
     *   - $existsLanguageSubdomain = false
     *     - example.com/en/api (404 Not Found)
     *     - example.com/en/api/create (404 Not Found)
     * - Creating the blacklisted URL
     *   - echo \yii\helpers\Html::a('API', ['api/index', Yii::$app->urlManager->queryParam => null]);
     */
    public $blacklist = [];
    /**
     * @var string the query parameter name that contains a language.
     * @see \yii\web\Request::getQueryParams()
     */
    public $queryParam = 'language';

    /**
     * @inheritdoc
     */
    public function init()
    {
        if (!$this->enablePrettyUrl) {
            throw new InvalidConfigException(
                'The "enablePrettyUrl" property must be set to "true"'
            );
        }
        if (is_callable($this->languages)) {
            $this->languages = call_user_func($this->languages);
        }
        if (!is_array($this->languages)) {
            throw new InvalidConfigException(
                'The "languages" property must be an array or callable function that returns an array'
            );
        }
        parent::init();
    }

    /**
     * @inheritdoc
     */
    public function parseRequest($request)
    {
        $pathInfo = $request->getPathInfo();
        if (!$this->existsLanguageSubdomain) {
            $language = explode(self::SEPARATOR_PATH, $pathInfo)[0];
            if (in_array($language, $this->languages)) {
                $pathInfo = ltrim($pathInfo, $language);
                if (!$this->isBlacklisted($pathInfo)) {
                    $request->setPathInfo($pathInfo);
                    $this->setQueryParam($request, $language);
                }
            }

            return parent::parseRequest($request);
        } else {
            $hostChunks = $this->getHostChunks($request);
            $language = ArrayHelper::getValue($hostChunks, 0);
            if (!in_array($language, $this->languages)) {
                return parent::parseRequest($request);
            } else {
                if ($this->isBlacklisted($pathInfo)) {
                    return false;
                } else {
                    $this->setQueryParam($request, $language);

                    return parent::parseRequest($request);
                }
            }
        }
    }

    /**
     * @inheritdoc
     */
    public function createUrl($params)
    {
        $request = Yii::$app->getRequest();
        if (!$this->existsLanguageSubdomain) {
            $language = ArrayHelper::remove(
                $params,
                $this->queryParam,
                $request->getQueryParam($this->queryParam)
            );
            $url = parent::createUrl($params);
            if (!in_array($language, $this->languages)) {
                return $url;
            } else {
                $baseUrl = $this->getBaseUrl();

                $url = implode(self::SEPARATOR_PATH, [
                    $baseUrl,
                    $language,
                    ltrim(str_replace('#' . $baseUrl, '', '#' . $url), '/'),
                ]);
                
                return $url;
            }
        } else {
            $language = ArrayHelper::remove($params, $this->queryParam);
            if (in_array($language, $this->languages)) {
                $hostChunks = $this->getHostChunks($request);
                if ($hostChunks[0] === self::DOMAIN_WWW) {
                    array_shift($hostChunks);
                }
                if (in_array($hostChunks[0], $this->languages)) {
                    $hostChunks[0] = $language;
                } else {
                    array_unshift($hostChunks, $language);
                }
                $protocol = ($request->getIsSecureConnection()) ? 'https' : 'http';
                $protocol .= '://';
                $host = implode(self::SEPARATOR_HOST, $hostChunks);
                $url = parent::createUrl($params);

                return $protocol . $host . $url;
            }

            return parent::createUrl($params);
        }
    }

    /**
     * Returns the "Host" header value splitted by the separator.
     *
     * @see \cetver\LanguageUrlManager\UrlManager::SEPARATOR_HOST
     *
     * @param Request $request the Request component instance.
     *
     * @return array
     */
    protected function getHostChunks(Request $request)
    {
        $host = parse_url($request->getHostInfo(), PHP_URL_HOST);

        return explode(self::SEPARATOR_HOST, $host);
    }

    /**
     * Sets the query parameter that contains a language.
     *
     * @param Request $request the Request component instance.
     * @param string $value a language value.
     */
    protected function setQueryParam(Request $request, $value)
    {
        $queryParams = $request->getQueryParams();
        $queryParams[$this->queryParam] = $value;
        $request->setQueryParams($queryParams);
    }

    /**
     * Returns whether the path info is blacklisted.
     *
     * @see $blacklist
     *
     * @param string $pathInfo the path info of the currently requested URL.
     *
     * @return bool
     */
    protected function isBlacklisted($pathInfo)
    {
        $pathInfo = ltrim($pathInfo, self::SEPARATOR_PATH);
        foreach ($this->blacklist as $pattern) {
            if (preg_match($pattern, $pathInfo)) {
                return true;
            }
        }

        return false;
    }
}
