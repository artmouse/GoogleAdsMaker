# Google Ads free tool

Данная программная заготовка позволит вам автоматизировать процесс создания кампаний, групп, объявлений и ключевых слов
на основе фидов вашего сайта в формате xml.

> Данный код является частным решением для конкретного формата фида, и для вашего сайта он должен быь адаптирован программистом <br>
Утилита поставляется как заготовка и требует обработки программистом для её дальнейшего использования.

### Что нужно для начала работы

*   System requirements and dependencies can be found in `composer.json` of this
    library. See [this page](https://getcomposer.org/doc/01-basic-usage.md) for
    more details.
*   This library depends on [Composer](https://getcomposer.org/). If you don't
    have it installed on your computer yet, follow the
    [installation guide for Linux/Unix/OS X](https://getcomposer.org/doc/00-intro.md#installation-linux-unix-osx)
    or [installation guide for Windows](https://getcomposer.org/doc/00-intro.md#installation-windows).
    For the rest of this guide, we're assuming that you're using
    Linux/Unix/OS X and have Composer installed [globally](https://getcomposer.org/doc/00-intro.md#globally),
    thus, your installed Composer is available on the command line as `composer`.
*   **To use this library to connect to AdWords API, you need an
    [OAuth 2 client ID and secret](https://developers.google.com/adwords/api/docs/guides/first-api-call#oauth2_client_id_and_client_secret),
    as well as a [developer token](https://developers.google.com/adwords/api/docs/guides/first-api-call#developer_token).
    Make sure you've written down these credentials in advance.**
    
 ### Setting up your OAuth2 credentials
 
 The AdWords and Ad Manager APIs use
 [OAuth2](https://oauth.net/2/) as the authentication mechanism. Follow the
 appropriate guide below based on your use case.
 
 **If you're accessing an API using your own credentials...**
 
 *   [Using
     AdWords](https://github.com/googleads/googleads-php-lib/wiki/API-access-using-own-credentials-\(installed-application-flow\))
 *   [Using
     Ad Manager](https://github.com/googleads/googleads-php-lib/wiki/API-access-using-own-credentials-\(server-to-server-flow\))
 
 **If you're accessing an API on behalf of clients...**
 
 *   [Using AdWords or
     Ad Manager](https://github.com/googleads/googleads-php-lib/wiki/API-access-on-behalf-of-your-clients-\(web-flow\))
     
### Что нужно для запуска приложения
-   указать все константы в классе GoogleAuth.php
    >  const CLIENT_ID = 'xxx';
         const CLIENT_SECRET = 'xxx';
         const DEVELOPER_TOKEN = 'xxx';
         const REFRESH_TOKEN = 'xxx';
         const CUSTOMER_ID = 'xxx';
-   указать ссылку на XML фид сайта в классе Account.php
    > $myXMLData = 'https://site.com/google.xml';
-   указать список тегов, которые надо парсить из XML файла (метод prepareFile)
    > $fields = array('id','title', 'link', 'brand', 'price', 'sale_price', 'model', 'product_category', 'product_arctl', 'color', 'max_handling_time');