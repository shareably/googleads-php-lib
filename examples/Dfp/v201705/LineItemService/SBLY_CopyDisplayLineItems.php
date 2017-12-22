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
use Google\AdsApi\Dfp\v201705\Size;
use Google\AdsApi\Dfp\v201705\CostType;
use Google\AdsApi\Dfp\v201705\CustomTargetingService;
use Google\AdsApi\Dfp\v201705\CustomCriteria;


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

    $creativeIdsToCopy = [
      '138220870795',
      '138220908189',
      '138220908216',
      '138220908225',
      '138220908219',
      '138220908228',
      '138220908234',
      '138220908237'
    ];
    $lineItemIdToCopy = '4528813149';
    $startingRate = 20.00;
    $endingRate = 20.00;
    $hb_pb_key_id = 473282;

    $lineItemService = $dfpServices->get($session, LineItemService::class);
    $creativeService = $dfpServices->get($session, CreativeService::class);
    $licaService = $dfpServices->get($session, LineItemCreativeAssociationService::class);
    $customTargetingService =
        $dfpServices->get($session, CustomTargetingService::class);

    // Create line item to copy
    $statementBuilder = (new StatementBuilder())
        ->Where('lineItemId = :lineItemId')
        ->WithBindVariableValue('lineItemId', $lineItemIdToCopy);

    $page = $lineItemService->getLineItemsByStatement(
        $statementBuilder->ToStatement());

    $lineItems = array();

    $lineItemToCopy = $page->getResults()[0];

    for ($x = $startingRate; $x <= $endingRate; $x = $x + 0.01) {
      print_r(sprintf('%.2f', $x));

      $newLineItem = clone $lineItemToCopy;
      $newLineItem->setName(sprintf('pb_display_%.2f', $x));
      $newLineItem->setStartDateTimeType(StartDateTimeType::IMMEDIATELY);
      $newLineItem->setCostType(CostType::CPM);
      $newLineItem->setCostPerUnit(new Money('USD', $x * 1000000));

      $statementBuilder = (new StatementBuilder())
          ->where('name = :name AND customTargetingKeyId = :customTargetingKeyId');
      $statementBuilder->withBindVariableValue(
          'name', sprintf('%.2f', $x));
      $statementBuilder->withBindVariableValue(
          'customTargetingKeyId', $hb_pb_key_id);
      $resultsPage = $customTargetingService->getCustomTargetingValuesByStatement(
          $statementBuilder->toStatement());
      $newCustomTargetingKeyId = $resultsPage->getResults()[0]->getId();

      // this is broken... value id always goes to last one in loop
      $newChildren = array();
      foreach($newLineItem->getTargeting()->getCustomTargeting()->getChildren()[0]->getChildren() as $child) {
        if ($child->getKeyId() == $hb_pb_key_id) {
          $newCustomCriteria = new CustomCriteria();
          $newCustomCriteria->setKeyId($hb_pb_key_id);
          $newCustomCriteria->setValueIds([$newCustomTargetingKeyId]);
          $newCustomCriteria->setOperator('IS');
          array_push($newChildren, $newCustomCriteria);
        } else {
          array_push($newChildren, $child);
        }
      }
      $newLineItem->getTargeting()->getCustomTargeting()->getChildren()[0]->setChildren($newChildren);

      array_push($lineItems, $newLineItem);
    }
    $createdLineItems = $lineItemService->createLineItems($lineItems);
    $licas = array();

    foreach ($createdLineItems as $createdLineItem) {
      printf("Line item with name '%s' was created.\n", $createdLineItem->getName());

      foreach ($creativeIdsToCopy as $creativeIdToCopy) {
        $lica = new LineItemCreativeAssociation();
        $lica->setCreativeId($creativeIdToCopy);
        $lica->setLineItemId($createdLineItem->getId());
        $lica->setSizes([
          new Size(300, 100, false),
          new Size(300, 250, false),
          new Size(300, 600, false),
          new Size(320, 50, false),
          new Size(336, 280, false),
          new Size(728, 90, false),
          new Size(970, 90, false),
        ]);

        array_push($licas, $lica);
      }
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
