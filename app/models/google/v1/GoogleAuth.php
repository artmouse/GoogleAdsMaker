<?php

namespace Google\AdsApi\Examples\Auth;
namespace Google\AdsApi\Examples\AdWords\v201802\BasicOperations;
namespace Google\AdsApi\Examples\AdWords\v201802\AccountManagement;
namespace Google\AdsApi\AdWords\v201802\mcm;
namespace Adson\Model\Google\v1;

use Google\AdsApi\AdWords\v201806\mcm\CustomerService as CustomerService;
use Illuminate\Database\Eloquent\Model as Eloquent;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Query\Builder;

use \Interop\Container\ContainerInterface as ContainerInterface;
use \Psr\Http\Message\ServerRequestInterface;
use \Psr\Http\Message\ResponseInterface;
use Adson\Middleware\Validator;
use Adson\Middleware\Notify;


use Slim\Http\Request;
use Slim\Http\Response;
use Slim\Http\Cookies;
use Slim\Http\Headers;


use Google\Auth\CredentialsLoader;
use Google\Auth\OAuth2;
use Google\AdsApi\AdWords\AdWordsServices;
use Google\AdsApi\AdWords\AdWordsSessionBuilder;
use Google\AdsApi\AdWords\v201802\o;
use Google\AdsApi\AdWords\v201802\cm\Campaign;
use Google\AdsApi\AdWords\v201802\cm\CampaignService;
use Google\AdsApi\Common\OAuth2TokenBuilder;
use Google\AdsApi\AdWords\v201802\cm\OrderBy;
use Google\AdsApi\AdWords\v201802\cm\Paging;
use Google\AdsApi\AdWords\v201802\cm\Selector;
use Google\AdsApi\AdWords\v201802\cm\SortOrder as SortOrder;
use Google\AdsApi\AdWords\AdWordsSession;
use Google\AdsApi\AdWords\v201802\mcm\ManagedCustomer;
use Google\AdsApi\AdWords\v201802\mcm\ManagedCustomerService as ManagedCustomerService;
use Google\AdsApi\AdWords\v201802\mcm\getCustomersResponse;
use Google\AdsApi\AdWords\v201802\mcm\Customer;
use Google\AdsApi\Common\Util\OAuth2TokenRefresher;
class GoogleAuth extends Eloquent
{
    const PAGE_LIMIT = 500;
    const ADWORDS_API_SCOPE = 'https://www.googleapis.com/auth/adwords';
    const DFP_API_SCOPE = 'https://www.googleapis.com/auth/dfp';
    const AUTHORIZATION_URI = 'https://accounts.google.com/o/oauth2/v2/auth';
    const REDIRECT_URI = 'urn:ietf:wg:oauth:2.0:oob';
    const CLIENT_ID = 'xxx';
    const CLIENT_SECRET = 'xxx';
    const DEVELOPER_TOKEN = 'xxx';
    const REFRESH_TOKEN = 'xxx';
    const CUSTOMER_ID = 'xxx';

    protected $oauth;
    protected $refresh_token;
    protected $oAuth2Credential;
    protected $session;
    protected $origin;
    protected $redirectUri;

    protected $table = 'products';

    protected $fillable = [
        'id',
        'productId',
        'productTitle',
        'googleCheck',
    ];

    public function __construct(ContainerInterface $container)
    {
        /*parent::__construct();*/
        $this->container = $container;
        $this->view = $container->get('view');
        $this->DB = $container->get('db');

        $origin = $this->container->request->getHeader('HTTP_ORIGIN');
        $redirectUri = $this->container->request->getHeader('X-Google-Auth-Link');

        $this->redirectUri = reset($redirectUri);
        $this->origin = reset($origin);
        if (empty($this->origin)) {
            $this->origin = $_SERVER['REQUEST_SCHEME'].'://'.$_SERVER['HTTP_HOST'];
        }

        /* google oauth instance */
        $oauth2 = new OAuth2([
            'authorizationUri' => 'https://accounts.google.com/o/oauth2/v2/auth',
            'tokenCredentialUri' => 'https://www.googleapis.com/oauth2/v4/token',
            'redirectUri' => $this->origin.$this->redirectUri,
            'clientId' => self::CLIENT_ID,
            'clientSecret' => self::CLIENT_SECRET,
            'scope' => self::ADWORDS_API_SCOPE,
        ]);
        $this->oauth = $oauth2;
    }

    public function getAuthLink(Request $request, Response $response)
    {
        $statusCode = 200;
        $params = array();
        $errors = array();
        $api_response = array();
        $api_response['body'] = array();

        $params = $request->getParams();
        $params = (object)Validator::clearArray($params);

        if(empty($params->token)) {
            $errors['token'] = 'empty_token';
        }

        if(empty($errors)) {
            try {
                if(!$this->DB->table('users')->where('token', $params->token)->exists()) {
                    $statusCode = 400;
                    throw new \Exception('token_not_exist');
                } else {
                    $this->oauth->setState(sha1(openssl_random_pseudo_bytes(1024)));
                    $oauth2state = $this->oauth->getState();
                    $config = [
                        'access_type' => 'offline',
                        'prompt' => 'consent',
                    ];

                    $this->oauth->buildFullAuthorizationUri($config);

                    $api_response['body']['auth']['link'] = (string)$this->oauth->buildFullAuthorizationUri($config);
                }

            } catch ( \Exception $e) {
                $errors['database'] = 'not exists';
                $errors['description'] = $e->getMessage();
                $statusCode = 401;
            }
        } else {
            $statusCode = 401;
        }
        $api_response['errors'] = $errors;
        return $response->withJson($api_response)->withStatus($statusCode);
    }

    public function getAuthToken(Request $request, Response $response)
    {

        $statusCode = 200;
        $params = array();
        $errors = array();
        $api_response = array();
        $api_response['body'] = array();

        $params = $request->getParams();
        $params = (object)Validator::clearArray($params);


        if(empty($params->code)) {
            $errors['code'] = 'empty_code';
        }
        if(empty($params->token)) {
            $errors['token'] = 'empty_token';
        }

        if(empty($errors)) {
            try {
                if(!$this->DB->table('users')->where('token', $params->token)->exists()) {
                    $statusCode = 400;
                    throw new \Exception('token_not_exist');
                } else {
                    $this->oauth->setCode($params->code);
                    $authToken = $this->oauth->fetchAuthToken();
                    $refresh_token = $authToken['refresh_token'];
                    $this->refresh_token = $refresh_token;
                    $this->oauth->setState(sha1(openssl_random_pseudo_bytes(1024)));

                    $api_response['body']['refresh_token'] = $refresh_token;
                    $api_response['body']['token'] = $authToken;
                    $api_response['body']['token_info']['AccessToken'] = $this->oauth->getAccessToken();
                    $api_response['body']['token_info']['RefreshToken'] = $this->oauth->getRefreshToken();
                    $api_response['body']['token_info']['Code'] = $this->oauth->getCode();
                    $api_response['body']['token_info']['IssuedAt'] = $this->oauth->getIssuedAt();
                    $api_response['body']['token_info']['ExpiresIn'] = $this->oauth->getExpiresIn();

                    $oAuth2Credential = (new OAuth2TokenBuilder())
                        ->withClientId(self::CLIENT_ID)
                        ->withClientSecret(self::CLIENT_SECRET)
                        ->withRefreshToken($refresh_token)
                        ->build();

                    /* создаем сессию для работы с приложением */
                    $session = (new AdWordsSessionBuilder())
                        ->withDeveloperToken(self::DEVELOPER_TOKEN)
                        ->withOAuth2Credential($oAuth2Credential)
                        ->build();

                    /* получаем customer_id по факту id аккаунта Adwords для работы */
                    $adWordsServices = new AdWordsServices();
                    $customerService = $adWordsServices->get($session, CustomerService::class);


                    $cliendData = $customerService->getCustomers();

                    $accounts = array();
                    foreach ($cliendData as $clientAcc) {
                        $customerID = $clientAcc->getCustomerId();
                        $accounts[$customerID] = $clientAcc->getDescriptiveName();
                    }

                }
            } catch (\Exception $e) {
                $errors['database'] = 'not exists';
                $errors['description'] = $e->getMessage();
                $statusCode = 401;
            }
        } else {
            $statusCode = 400;
        }

        $api_response['errors'] = $errors;
        return $response->withJson($api_response)->withStatus($statusCode);
    }
}