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

    $orderId = '2211097857';
    $hb_pb_key_id = 473282;

    $creativeIdsToCopy = [
      '138220878803',
      '138220878836',
      '138220878848',
      '138220878860',
      '138220869922',
      '138220878851',
      '138220878863',
      '138220878866'
    ];

    $lineItemService = $dfpServices->get($session, LineItemService::class);
    $customTargetingService =
        $dfpServices->get($session, CustomTargetingService::class);
    $licaService = $dfpServices->get($session, LineItemCreativeAssociationService::class);

    $statementBuilder = (new StatementBuilder())
        ->where('customTargetingKeyId = :customTargetingKeyId');
    $statementBuilder->withBindVariableValue(
        'customTargetingKeyId', $hb_pb_key_id);
    $page = $customTargetingService->getCustomTargetingValuesByStatement(
        $statementBuilder->toStatement());

    $rateToId = array();

    foreach ($page->getResults() as $customTargetingValue) {
      $rateToId[$customTargetingValue->getName()] = $customTargetingValue->getId();
    }

    $statementBuilder = (new StatementBuilder())
        ->Where('orderId = :orderId')
        ->WithBindVariableValue('orderId', $orderId);

    $page = $lineItemService->getLineItemsByStatement(
        $statementBuilder->ToStatement());

    $lineItemsToUpdate = array();
    foreach ($page->getResults() as $lineItem) {

      $rate = explode('_', $lineItem->getName())[2];
      $lineItem->setCostPerUnit(new Money('USD', $rate * 1000000));
      $newCustomTargetingKeyId = $rateToId[$rate];

      foreach($lineItem->getTargeting()->getCustomTargeting()->getChildren()[0]->getChildren() as $child) {
        if ($child->getKeyId() == $hb_pb_key_id) {
          $child->setValueIds([$newCustomTargetingKeyId]);
        }
      }
      $lineItem->setName(str_replace('video', 'display', $lineItem->getName()));

      $licaStatementBuilder = (new StatementBuilder())
          ->where('lineItemId = :lineItemId');
      $licaStatementBuilder->withBindVariableValue(
          'lineItemId', $lineItem->getId());
      $licaResults = $licaService->getLineItemCreativeAssociationsByStatement(
          $licaStatementBuilder->toStatement());

      if (count($licaResults->getResults()) == 0) {
        $licas = array();
        foreach ($creativeIdsToCopy as $creativeIdToCopy) {
          $lica = new LineItemCreativeAssociation();
          $lica->setCreativeId($creativeIdToCopy);
          $lica->setLineItemId($lineItem->getId());
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

      //array_push($lineItemsToUpdate, $lineItem);
    }

    // $updatedLineItems = $lineItemService->updateLineItems($lineItemsToUpdate);

    // foreach ($updatedLineItems as $updatedLineItem) {
    //   printf("Line item with name '%s' was updated.\n", $updatedLineItem->getName());
    // }
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
