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
    $lineItemService =
        $dfpServices->get($session, LineItemService::class);

    // Create a statement to select line items.
    $statementBuilder = (new StatementBuilder())
        ->Where('lineItemId = :lineItemId')
        ->WithBindVariableValue('lineItemId', '4366027857');

    $page = $lineItemService->getLineItemsByStatement(
        $statementBuilder->ToStatement());

    $lineItems = array();

    $lineItemToCopy = $page->getResults()[0];

    foreach ($page->getResults() as $lineItem) {
        
      print_r($lineItem);

      if ($lineItem->getIsArchived() == false) {


        // $lineItem->targeting->customTargeting->children = array($lineItem->targeting->customTargeting->children[0], $customCriteria1);
        // $lineItem->targeting->customTargeting->logicalOperator = 'AND';

        // printf("current amount: %d \n", $lineItem->getCostPerUnit()->getMicroAmount());

        // $current_amount = $lineItem->getCostPerUnit()->getMicroAmount();
        // $new_amount = $current_amount * 3;

        // $lineItem->getValueCostPerUnit()->setMicroAmount($new_amount);

        // printf("new amount: %d \n", $new_amount);

        // $hb_pb = $lineItem->getTargeting()->getCustomTargeting()->getChildren()[0]->getChildren()[0];
        // var_dump($hb_pb);

        // $lineItem->getTargeting()->getCustomTargeting()->setChildren(array($hb_pb));

        // $customTargeting = $lineItem->getTargeting()->getCustomTargeting();
        // var_dump($customTargeting);

        // echo("\n");

        $lineItem 

        array_push($lineItems, $lineItem);
      }
    }

    $lineItemService->createLineItems($lineItems);

    foreach ($lineItems as $createdLineItem) {
      printf("Line item with ID %d, name '%s' was updated.\n", $createdLineItem->getId(), $createdLineItem->getName());
    }

    // foreach ($lineItems as $updatedLineItem) {
    //   printf("Line item with ID %d, name '%s' was updated.\n",
    //       $updatedLineItem->getId(), $updatedLineItem->getName());
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
