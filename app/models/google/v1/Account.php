<?php

namespace Google\AdsApi\Examples\Auth;
namespace Google\AdsApi\Examples\AdWords\v201809\BasicOperations;
namespace Google\AdsApi\Examples\AdWords\v201809\AccountManagement;
namespace Google\AdsApi\Examples\AdWords\v201809\ShoppingCampaigns;
namespace Google\AdsApi\AdWords\v201809\mcm;
namespace Adson\Model\Google\v1;

use Google\AdsApi\AdWords\v201809\cm\CpcBid;

use \Interop\Container\ContainerInterface as ContainerInterface;
use Adson\Middleware\Validator;

use PHPMailer\PHPMailer\Exception;
use Slim\Http\Request;
use Slim\Http\Response;


use Google\AdsApi\AdWords\AdWordsServices;
use Google\AdsApi\AdWords\AdWordsSessionBuilder;

use Google\AdsApi\AdWords\v201809\cm\Campaign;
use Google\AdsApi\AdWords\v201809\cm\CampaignService;
use Google\AdsApi\Common\OAuth2TokenBuilder;

use Google\AdsApi\AdWords\v201809\cm\NetworkSetting;

use Google\AdsApi\AdWords\v201809\cm\AdGroupService;
//use Google\AdsApi\AdWords\v201809\cm\AdGroupAdService;

use GuzzleHttp\Exception\RequestException;
use Google\AdsApi\AdWords\v201809\cm\DateRange;

use Google\AdsApi\AdWords\Shopping\v201809\ProductPartitions;
use Google\AdsApi\AdWords\v201809\cm\ProductPartitionType;
use Google\AdsApi\AdWords\v201809\cm\ProductPartition;
use Google\AdsApi\AdWords\v201809\cm\ProductBiddingCategory;
use Google\AdsApi\AdWords\v201809\cm\ProductBrand;
use Google\AdsApi\AdWords\v201809\cm\ProductCanonicalCondition;
use Google\AdsApi\AdWords\v201809\cm\ProductCanonicalConditionCondition;
use Google\AdsApi\AdWords\v201809\cm\ProductDimensionType;
use Google\AdsApi\AdWords\v201809\cm\ProductDimension;
use Google\AdsApi\AdWords\v201809\cm\ProductOfferId;
use Google\AdsApi\AdWords\v201809\cm\AdGroupCriterionOperation;
use Google\AdsApi\AdWords\v201809\cm\AdGroupCriterionService;
use Google\AdsApi\AdWords\v201809\cm\BiddableAdGroupCriterion;
use Google\AdsApi\AdWords\v201809\cm\Criterion;
use Google\AdsApi\AdWords\v201809\cm\Operator;
use Google\AdsApi\AdWords\v201809\cm\UrlList;
//use Google\AdsApi\AdWords\v201809\cm\BiddingStrategyConfiguration;
use Google\AdsApi\AdWords\v201809\cm\Money;
use Google\AdsApi\AdWords\v201809\cm\Bids;
use Google\AdsApi\AdWords\v201809\cm\UserStatus;


use Google\AdsApi\AdWords\v201809\cm\AdvertisingChannelType;
use Google\AdsApi\AdWords\v201809\cm\BiddingStrategyConfiguration;
use Google\AdsApi\AdWords\v201809\cm\BiddingStrategyType;
use Google\AdsApi\AdWords\v201809\cm\Budget;
use Google\AdsApi\AdWords\v201809\cm\BudgetBudgetDeliveryMethod;
use Google\AdsApi\AdWords\v201809\cm\BudgetOperation;
use Google\AdsApi\AdWords\v201809\cm\BudgetService;
use Google\AdsApi\AdWords\v201809\cm\FrequencyCap;
use Google\AdsApi\AdWords\v201809\cm\CampaignOperation;
use Google\AdsApi\AdWords\v201809\cm\CampaignStatus;
use Google\AdsApi\AdWords\v201809\cm\ManualCpcBiddingScheme;
use Google\AdsApi\AdWords\v201809\cm\AdGroup;
use Google\AdsApi\AdWords\v201809\cm\AdGroupType;
use Google\AdsApi\AdWords\v201809\cm\AdGroupOperation;
use Google\AdsApi\AdWords\v201809\cm\AdGroupStatus;
use Google\AdsApi\AdWords\v201809\cm\ExpandedTextAd;
use Google\AdsApi\AdWords\v201809\cm\AdGroupAd;
use Google\AdsApi\AdWords\v201809\cm\AdGroupAdOperation;
use Google\AdsApi\AdWords\v201809\cm\AdGroupAdStatus;
use Google\AdsApi\AdWords\v201809\cm\AdGroupAdService;
use Google\AdsApi\AdWords\v201809\cm\Keyword;
use Google\AdsApi\AdWords\v201809\cm\KeywordMatchType;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use Monolog\Logger;
use Monolog\Handler\ErrorLogHandler;
use Monolog\Formatter\LineFormatter;
use Monolog\Handler\StreamHandler;

define('CAMPAIGN_VERSION', 'v1.0');
class Account extends GoogleAuth
{

    public $google_metrics;
    protected $products = 'products';
    protected $campaigns = 'campaigns';
    protected $groups = 'groups';
    protected $ads = 'ads';
    protected $DB;
    protected $container;
    protected $order = array("\\", "[", "]", "@", ",", "+", "%", "$", "^", "#", "№", "/", "\r\n", "\n", "\t", "®");
    protected $replace = " ";
    protected $logger;


    public function __construct(ContainerInterface $container)
    {
        parent::__construct($container);
        $this->container = $container;
        $this->DB = $container->get('db');

        $settings = $container->get('settings')['access'];
        $this->logger = $container->get('logger');
    }

    public function prepareFile(Request $request, Response $response, $args)
    {
        $myXMLData = 'https://site.com/google.xml';
        $context = stream_context_create(array('http' => array('header' => 'Accept: application/xml')));

        $xml = file_get_contents($myXMLData, false, $context);
        $xml = simplexml_load_string($xml);

        $fields = array('id','title', 'link', 'brand', 'price', 'sale_price', 'model', 'product_category', 'product_arctl', 'color', 'max_handling_time');

        $i = 0;
        foreach ($xml->channel->item as $entry) {
            $g = $entry->children("http://base.google.com/ns/1.0");
            foreach ($fields as $field) {
                if ($entry->{$field} == '') {
                    $products[$i][$field] = (string)$g->{$field};
                } else {
                    $products[$i][$field] = (string)$entry->{$field};
                }
            }
            $i++;
        }

        if (!empty($products)) {
            foreach ($products as $product) {
                if (!empty($product['max_handling_time'])) {
                    $rawLine = $product['max_handling_time'];
                    if (stristr($rawLine, '#')) {
                        $explodedId = explode('#', $rawLine);
                        $product['max_handling_time'] = trim(preg_replace("/[^0-9]+/", "", $explodedId[0]));
                    } else {
                        $product['max_handling_time'] = trim(preg_replace("/[^0-9]+/", "", $rawLine));
                    }
                }
                $product['title'] = str_replace($this->order, $this->replace, $product['title']);
                $product['is_done'] = 0;
                if ($this->DB->table($this->table)->where('id', $product['id'])->exists()) {
                    $this->DB->table($this->table)->where([
                        ['id', '=', $product['id']],
                        ['sale_price', '<>', $product['sale_price']],
                    ])->update($product);
                } else {
                    $this->DB->table($this->table)->insert($product);
                }
            }
        }

        $api_response['body'] = [__METHOD__];

        return $response->withJson($api_response)->withStatus(200);

    }


    public function checkProducts(Request $request, Response $response)
    {


        $productsRaw = $this->DB->table($this->table)->where('is_done', 0)->limit(25)->orderBy('id', 'desc')->get();
        $products = $productsRaw->toArray();
        if (count($products) > 0) {
            $preparedProducts = [];
            foreach ($products as $product) {
                $product = (array)$product;
                $preparedProducts[$product['product_category']][$product['max_handling_time']][] = $product;
            }

            /* создаем сессию для работы с АПИ */
            $oAuth2Credential = (new OAuth2TokenBuilder())
                ->withClientId(parent::CLIENT_ID)
                ->withClientSecret(parent::CLIENT_SECRET)
                ->withRefreshToken(parent::REFRESH_TOKEN)
                ->build();
            $session = (new AdWordsSessionBuilder())
                ->withClientCustomerId(parent::CUSTOMER_ID)
                ->withDeveloperToken(parent::DEVELOPER_TOKEN)
                ->withOAuth2Credential($oAuth2Credential)
                ->build();


            $adWordsServices = new AdWordsServices();
            $budgetService = $adWordsServices->get($session, BudgetService::class);
            $adGroupService = $adWordsServices->get($session, AdGroupService::class);
            $adGroupAdService = $adWordsServices->get($session, AdGroupAdService::class);
            $adGroupCriterionService = $adWordsServices->get($session, AdGroupCriterionService::class);


            foreach ($preparedProducts as $CampaignName => $products) {

                $campaignExist = $this->DB->table($this->campaigns)->where('name', $CampaignName.CAMPAIGN_VERSION)->limit(1)->orderBy('id', 'desc')->get();
                $campaignExist = reset($campaignExist->toArray());
                /* если есть кампания, возьмем ее ID */
                if ($campaignExist->id) {
                    $res['CampaignID'] = $campaignExist->id;
                } else {
                    /*если кампании нет, создаем новую */
                    $res = $this->createCampaign($session, $CampaignName.CAMPAIGN_VERSION, $budgetService, $adWordsServices);
                }

                if (empty($res['error'])) {
                    $campaignId = $res['CampaignID'];

                    foreach ($products as $product) {

                        $groupCheck = $this->DB->table($this->groups)->where('name', $product[0]['title'] . ' '. $product[0]['max_handling_time'])->limit(1)->orderBy('id', 'desc')->get();
                        $groupCheck = reset($groupCheck->toArray());

                        if (!empty($groupCheck->id)) {
                            $result = (array)$groupCheck;
                        } else {
                            $result = $this->createAdGroup($adGroupService, $product[0], $campaignId);
                            sleep(0.2);
                            if (!empty($result['id'])) {
                                $this->createKeywords($adGroupCriterionService, $result['id'], $product);
                            }
                        }
                        sleep(0.2);
                        if (!empty($result['id'])) {
                            $adsRaw = $this->DB->table($this->ads)->where([
                                ['productId', '=', $product['id']],
                                ['adGroupId', '=', $result['id']],
                                ['is_done', '=', 0],
                            ])->get();
                            $adsList = $adsRaw->toArray();
                            if (!empty($adsList)) {
                                /* обновим обьявление */
                            } else {
                                /* создадим обьявление */
                                $this->createAd($session, $adWordsServices, $adGroupAdService, $product, $result['id']);
                            }
                        }
                    }
                }
            }
        }
    }

    public function createCampaign($session, $campaignName, $budgetService, $adWordsServices)
    {
        /* СОЗДАЕМ КАМПАНИЮ */
        // Create the shared budget (required).
        try {
            $budget = new Budget();
            $budget->setName($campaignName . uniqid());
            $money = new Money();
            $money->setMicroAmount(5000000);
            $budget->setAmount($money);
            $budget->setDeliveryMethod(BudgetBudgetDeliveryMethod::STANDARD);

            $operations = [];

            // Create a budget operation.
            $operation = new BudgetOperation();
            $operation->setOperand($budget);
            $operation->setOperator(Operator::ADD);
            $operations[] = $operation;

            // Create the budget on the server.
            $result = $budgetService->mutate($operations);
            $budget = $result->getValue()[0];

            $campaignService = $adWordsServices->get($session, CampaignService::class);

            $operations = [];

            // Create a campaign with required and optional settings.
            $campaign = new Campaign();
            $campaign->setName($campaignName);
            $campaign->setAdvertisingChannelType(AdvertisingChannelType::SEARCH);

            // Set shared budget (required).
            $campaign->setBudget(new Budget());
            $campaign->getBudget()->setBudgetId($budget->getBudgetId());

            // Set bidding strategy (required).
            $biddingStrategyConfiguration = new BiddingStrategyConfiguration();
            $biddingStrategyConfiguration->setBiddingStrategyType(
                BiddingStrategyType::MANUAL_CPC
            );

            // You can optionally provide a bidding scheme in place of the type.
            $biddingScheme = new ManualCpcBiddingScheme();
            $biddingStrategyConfiguration->setBiddingScheme($biddingScheme);

            $campaign->setBiddingStrategyConfiguration($biddingStrategyConfiguration);

            // Set network targeting (optional).
            $networkSetting = new NetworkSetting();
            $networkSetting->setTargetGoogleSearch(true);
            $networkSetting->setTargetSearchNetwork(false);
            $networkSetting->setTargetContentNetwork(false);
            $campaign->setNetworkSetting($networkSetting);

            $campaign->setStatus(CampaignStatus::PAUSED);
            // Create a campaign operation and add it to the operations list.
            $operation = new CampaignOperation();
            $operation->setOperand($campaign);
            $operation->setOperator(Operator::ADD);
            $operations[] = $operation;

            // Create the campaigns on the server and print out some information for
            // each created campaign.
            $result = $campaignService->mutate($operations);
            $createdCampaign = $result->getValue();

            $CampaignID = $createdCampaign[0]->getId();
            $response['CampaignID'] = $CampaignID;
            $response['error'] = [];

            $dbCampaign['name'] = $campaignName;
            $dbCampaign['id'] = $CampaignID;
            $this->DB->table($this->campaigns)->insert($dbCampaign);

        } catch (\Exception $e) {
            $response['error'] = $e->getMessage();
            $this->logger->info('FunctionError', array('error' => $response['error'], 'request' => 'createCampaign', 'withName'=>$campaignName));
        }

        return $response;
    }
    public function createAdGroup($adGroupService, $adwordsItem, $CampaignID)
    {

        try {
            $operations = [];
            $AdGroupName = trim(str_replace($this->order, $this->replace, $adwordsItem['title'])) . ' '. $adwordsItem['max_handling_time'];

            $adGroup = new AdGroup();
            $adGroup->setCampaignId($CampaignID);
            $adGroup->setName($AdGroupName);
            $bid = new CpcBid();
            $money = new Money();
            $money->setMicroAmount(5000000);
            $bid->setBid($money);
            $biddingStrategyConfiguration = new BiddingStrategyConfiguration();
            $biddingStrategyConfiguration->setBids([$bid]);
            $adGroup->setBiddingStrategyConfiguration($biddingStrategyConfiguration);

            $adGroup->setStatus(AdGroupStatus::PAUSED);

            $operation = new AdGroupOperation();
            $operation->setOperand($adGroup);
            $operation->setOperator(Operator::ADD);
            $operations[] = $operation;
            $result = $adGroupService->mutate($operations);
            $newGroup = $result->getValue();
            $response['id'] = $newGroup[0]->getId();
            $response['error'] = [];

            $dbGroup['name'] = $AdGroupName;
            $dbGroup['id'] = $newGroup[0]->getId();
            $dbGroup['campaignId'] = $CampaignID;
            $this->DB->table($this->groups)->insert($dbGroup);

        } catch (\Exception $e) {
            $response['error'] = $e->getMessage();
            $this->logger->error('FunctionError', array('error' => $response['error'], 'request' => 'createAdGroup', 'withName'=>$AdGroupName));
        }
        return $response;
    }
    public function createAd($session, $adWordsServices, $adGroupAdService, $adwordsItems, $GroupID)
    {
        try {
            $adProduct = $adwordsItems[0]; //Берем основной товар
            if (!empty($adProduct['brand']) && !empty($adProduct['model'])) {
                $brand = $adProduct['brand'];
                $model = $adProduct['model'];
                $line1 = $brand. ' '. $model;
            } else {
                $line1 = $adProduct['product_category'];
            }

            $prices = [];
            foreach ($adwordsItems as $adwordsItem) {
                if ($adwordsItem['sale_price']) {
                    $price = $adwordsItem['sale_price'];
                } else {
                    $price = $adwordsItem['price'];
                }
                $price = str_replace('UAH', ' ', $price);
                if ($price > 0) {
                    $prices[] = $price;
                }
            }
            $price = min($prices);

            if (strlen($line1) > 30 || empty($line1)) {
                $line1 = 'xxx';
            }


            $line1 = str_replace($this->order, $this->replace, $line1);
            $line2 = 'Купить от ' . $price . ' грн. Переходи';
            if (strlen($line2) > 30) {
                $line2 = 'Купить от ' . $price . ' грн.';
            }
            $line3 = 'Быстрая доставка. Рассрочка 0';

            $description = $adProduct['title'];
            if (strlen($description) > 90) {
                $description = 'Доставка по Украине от 1 дня';
            }

            $url = $adProduct['link'];

            $operations = [];

            $expandedTextAd = new ExpandedTextAd();
            $expandedTextAd->setHeadlinePart1(ucfirst($line1));
            $expandedTextAd->setHeadlinePart2(ucfirst($line2));
            $expandedTextAd->setHeadlinePart3($line3);
            $expandedTextAd->setDescription($description);
            $expandedTextAd->setFinalUrls([$url]);
            $expandedTextAd->setPath1('Хочешь скидку');
            $expandedTextAd->setPath2('Звони');

            $adGroupAd = new AdGroupAd();
            $adGroupAd->setAdGroupId(floatval($GroupID));
            $adGroupAd->setAd($expandedTextAd);
            $adGroupAd->setStatus(AdGroupAdStatus::PAUSED);
            $operation = new AdGroupAdOperation();
            $operation->setOperand($adGroupAd);
            $operation->setOperator(Operator::ADD);
            $operations[] = $operation;
            $result = $adGroupAdService->mutate($operations);

            $AD = $result->getValue();

            $dbGroup['id'] = $AD[0]->getAd()->getId();
            $dbGroup['is_done'] = 1;
            $dbGroup['adGroupId'] = $GroupID;
            $dbGroup['productId'] = $adwordsItem['id'];
            $this->DB->table($this->ads)->insert($dbGroup);

            $productUpd['is_done'] = 1;
            $this->DB->table($this->products)->where('max_handling_time', $adProduct['max_handling_time'])->update($productUpd);

            $response['error'] = [];
            $response['body'] = $dbGroup;

        } catch (\Exception $e) {
            $response['error'] = $e->getMessage();
            $this->logger->error('FunctionError', array('error' => $response['error'], 'request' => 'createAd', 'withProduct'=>print_r($adProduct, true)));
        }
        return $response;

    }

    public function createKeywords($adGroupCriterionService, $adGroupId, $productsGroup)
    {
        try {
            $operations = [];
            if (is_array($productsGroup)) {

            } else {
                $productsGroup = [$productsGroup];
            }
            foreach ($productsGroup as $product) {
                // Create the first keyword criterion.
                if (!empty($product['product_arctl'])) {
                    $keyword = new Keyword();
                    $keyword->setText($product['product_arctl']);
                    $keyword->setMatchType(KeywordMatchType::BROAD);
                    $adGroupCriterion = new BiddableAdGroupCriterion();
                    $adGroupCriterion->setAdGroupId(floatval($adGroupId));
                    $adGroupCriterion->setCriterion($keyword);
                    $adGroupCriterion->setUserStatus(UserStatus::PAUSED);

                    $bid = new CpcBid();
                    $money = new Money();
                    $money->setMicroAmount(500000);

                    $bid->setBid($money);
                    $biddingStrategyConfiguration = new BiddingStrategyConfiguration();
                    $biddingStrategyConfiguration->setBids([$bid]);
                    $adGroupCriterion->setBiddingStrategyConfiguration(
                        $biddingStrategyConfiguration
                    );

                    $operation = new AdGroupCriterionOperation();
                    $operation->setOperand($adGroupCriterion);
                    $operation->setOperator(Operator::ADD);
                    $operations[] = $operation;
                }


                if (!empty($product['product_arctl']) && !empty($product['brand'])) {
                    // Create the second keyword criterion.
                    $keyword = new Keyword();
                    $keyword->setText($product['product_arctl']. ' ' . $product['brand']);
                    $keyword->setMatchType(KeywordMatchType::BROAD);
                    $adGroupCriterion = new BiddableAdGroupCriterion();
                    $adGroupCriterion->setAdGroupId(floatval($adGroupId));
                    $adGroupCriterion->setCriterion($keyword);
                    $adGroupCriterion->setUserStatus(UserStatus::PAUSED);

                    $bid = new CpcBid();
                    $money = new Money();
                    $money->setMicroAmount(500000);

                    $bid->setBid($money);
                    $biddingStrategyConfiguration = new BiddingStrategyConfiguration();
                    $biddingStrategyConfiguration->setBids([$bid]);
                    $adGroupCriterion->setBiddingStrategyConfiguration(
                        $biddingStrategyConfiguration
                    );

                    $operation = new AdGroupCriterionOperation();
                    $operation->setOperand($adGroupCriterion);
                    $operation->setOperator(Operator::ADD);
                    $operations[] = $operation;
                }


                if (!empty($product['brand']) && !empty($product['model'])) {
                    // Create the third keyword criterion.
                    $keyword = new Keyword();
                    $keyword->setText($product['brand']. ' '. $product['model']);
                    $keyword->setMatchType(KeywordMatchType::BROAD);
                    $adGroupCriterion = new BiddableAdGroupCriterion();
                    $adGroupCriterion->setAdGroupId(floatval($adGroupId));
                    $adGroupCriterion->setCriterion($keyword);
                    $adGroupCriterion->setUserStatus(UserStatus::PAUSED);

                    $bid = new CpcBid();
                    $money = new Money();
                    $money->setMicroAmount(500000);

                    $bid->setBid($money);
                    $biddingStrategyConfiguration = new BiddingStrategyConfiguration();
                    $biddingStrategyConfiguration->setBids([$bid]);
                    $adGroupCriterion->setBiddingStrategyConfiguration(
                        $biddingStrategyConfiguration
                    );

                    $operation = new AdGroupCriterionOperation();
                    $operation->setOperand($adGroupCriterion);
                    $operation->setOperator(Operator::ADD);
                    $operations[] = $operation;
                }
            }

            if (!empty($operations)) {
                $result = $adGroupCriterionService->mutate($operations);
                foreach ($result->getValue() as $adGroupCriterion) {
                    printf(
                        "Keyword with text '%s', match type '%s', and ID %d was added.\n",
                        $adGroupCriterion->getCriterion()->getText(),
                        $adGroupCriterion->getCriterion()->getMatchType(),
                        $adGroupCriterion->getCriterion()->getId()
                    );
                }
            }


        } catch (\Exception $e) {
            $this->logger->error('FunctionError', array('error' => $e->getMessage(), 'request' => 'createKeyword'));

        }

    }

}
