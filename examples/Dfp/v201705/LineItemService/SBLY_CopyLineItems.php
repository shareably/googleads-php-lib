<?php
/**
 * Copyright 2016 Google Inc. All Rights Reserved.
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 *     http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 */
namespace Google\AdsApi\Examples\Dfp\v201705\LineItemService;

require __DIR__ . '/../../../../vendor/autoload.php';

use Google\AdsApi\Common\OAuth2TokenBuilder;
use Google\AdsApi\Dfp\DfpServices;
use Google\AdsApi\Dfp\DfpSession;
use Google\AdsApi\Dfp\DfpSessionBuilder;
use Google\AdsApi\Dfp\Util\v201705\StatementBuilder;
use Google\AdsApi\Dfp\v201705\LineItemService;
use Google\AdsApi\Dfp\v201705\StartDateTimeType;
use Google\AdsApi\Dfp\v201705\CreativeService;
use Google\AdsApi\Dfp\v201705\LineItemCreativeAssociation;
use Google\AdsApi\Dfp\v201705\LineItemCreativeAssociationService;
use Google\AdsApi\Dfp\v201705\Money;
use Google\AdsApi\Dfp\v201705\CostType;
use Google\AdsApi\Dfp\v201705\CustomTargetingService;


/**
 * This example gets all line items.
 *
 * <p>It is meant to be run from a command line (not as a webpage) and requires
 * that you've setup an `adsapi_php.ini` file in your home directory with your
 * API credentials and settings. See README.md for more info.
 */
class GetAllLineItems {

  public static function runExample(DfpServices $dfpServices,
      DfpSession $session) {

    $creativeIdToCopy = '138206953596';
    $lineItemIdToCopy = '4369864450';
    $startingRate = 49.52;
    $endingRate = 50.01;
    $hb_pb_key_id = 473282;

    $lineItemService = $dfpServices->get($session, LineItemService::class);
    $creativeService = $dfpServices->get($session, CreativeService::class);
    $licaService = $dfpServices->get($session, LineItemCreativeAssociationService::class);
    $customTargetingService =
        $dfpServices->get($session, CustomTargetingService::class);

    // Create creative to copy
    $creativeStatementBuilder = (new StatementBuilder())
        ->Where('creativeId = :creativeId')
        ->WithBindVariableValue('creativeId', $creativeIdToCopy);
    $page = $creativeService->getCreativesByStatement(
        $creativeStatementBuilder->toStatement());
    $creativeToCopy = $page->getResults()[0];
    $creatives = array();

    // Create line item to copy
    $statementBuilder = (new StatementBuilder())
        ->Where('lineItemId = :lineItemId')
        ->WithBindVariableValue('lineItemId', $lineItemIdToCopy);

    $page = $lineItemService->getLineItemsByStatement(
        $statementBuilder->ToStatement());

    $lineItems = array();

    $lineItemToCopy = $page->getResults()[0];

    for ($x = $startingRate; $x < $endingRate; $x = $x + 0.01) {
      print_r(sprintf('%.2f', $x));

      $newLineItem = clone $lineItemToCopy;
      $newLineItem->setName(sprintf('pb_video_%.2f', $x));
      $newLineItem->setStartDateTimeType(StartDateTimeType::IMMEDIATELY);
      $newLineItem->setCostType(CostType::CPM);
      $newLineItem->setCostPerUnit(new Money('USD', $x * 1000000));

      $statementBuilder = (new StatementBuilder())
          ->where('name = :name AND customTargetingKeyId = :customTargetingKeyId');
      $statementBuilder->withBindVariableValue(
          'name', sprintf('%.2f', $x));
      $statementBuilder->withBindVariableValue(
          'customTargetingKeyId', $hb_pb_key_id);
      $page = $customTargetingService->getCustomTargetingValuesByStatement(
          $statementBuilder->toStatement());
      $newCustomTargetingKeyId = $page->getResults()[0]->getId();

      $newLineItem->getTargeting()->getCustomTargeting()->getChildren()[0]->getChildren()[0]->setValueIds([$newCustomTargetingKeyId]);

      array_push($lineItems, $newLineItem);
    }

    $createdLineItems = $lineItemService->createLineItems($lineItems);

    foreach ($createdLineItems as $createdLineItem) {
      printf("Line item with name '%s' was created.\n", $createdLineItem->getName());

      $newCreative = clone $creativeToCopy;
      $newCreative->setId(null);

      array_push($creatives, $newCreative);
    }

    print_r($creatives);

    $createdCreatives = $creativeService->createCreatives($creatives);

    $licas = array();

    foreach ($createdCreatives as $i => $createdCreative) {
      printf(
          "Creative with ID %d and name '%s' was created.\n",
          $createdCreative->getId(), 
          $createdCreative->getName()
      );

      $lica = new LineItemCreativeAssociation();
      $lica->setCreativeId($createdCreatives[$i]->getId());
      $lica->setLineItemId($createdLineItems[$i]->getId());

      array_push($licas, $lica);
    }

    $results = $licaService->createLineItemCreativeAssociations($licas);

    foreach ($results as $i => $lica) {
      printf(
          "%d) LICA with line item ID %d, creative ID %d, and status '%s' was "
              . "created.\n",
          $i,
          $lica->getLineItemId(),
          $lica->getCreativeId(),
          $lica->getStatus()
      );
    }
  }

  public static function main() {
    // Generate a refreshable OAuth2 credential for authentication.
    $oAuth2Credential = (new OAuth2TokenBuilder())
        ->fromFile()
        ->build();

    // Construct an API session configured from a properties file and the OAuth2
    // credentials above.
    $session = (new DfpSessionBuilder())
        ->fromFile()
        ->withOAuth2Credential($oAuth2Credential)
        ->build();

    self::runExample(new DfpServices(), $session);
  }
}

GetAllLineItems::main();
