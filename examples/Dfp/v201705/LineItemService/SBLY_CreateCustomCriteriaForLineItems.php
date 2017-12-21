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

    $orderId = '457797122';
    $hb_pb_key_id = 11085518;

    $lineItemService = $dfpServices->get($session, LineItemService::class);
    $customTargetingService =
        $dfpServices->get($session, CustomTargetingService::class);

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
      //$rate = explode('_', $lineItem->getName())[2];
      //lineItem->setCostPerUnit(new Money('USD', $rate * 1000000));
      //$newCustomTargetingKeyId = $rateToId[$rate];
      //$lineItem->getTargeting()->getCustomTargeting()->getChildren()[0]->getChildren()[0]->setValueIds(447894808318);

      if (!$lineItem->getIsArchived()) {
        $oldCustomCriteria = clone $lineItem->getTargeting()->getCustomTargeting()->getChildren()[0]->getChildren()[0];
        $newCustomCriteria = clone $lineItem->getTargeting()->getCustomTargeting()->getChildren()[0]->getChildren()[0];
        $newCustomCriteria->setKeyId(11085518);
        $newCustomCriteria->setValueIds([447894808318]);
        $newCustomCriteria->setOperator('IS_NOT');
        $lineItem->getTargeting()->getCustomTargeting()->getChildren()[0]->setChildren([$oldCustomCriteria, $newCustomCriteria]);
        array_push($lineItemsToUpdate, $lineItem);
      }
    }
    $updatedLineItems = $lineItemService->updateLineItems($lineItemsToUpdate);

    foreach ($updatedLineItems as $updatedLineItem) {
      printf("Line item with name '%s' was updated.\n", $updatedLineItem->getName());
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
