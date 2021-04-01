<?php

require "vendor/autoload.php";

use BrowserStack\Local;

use Facebook\WebDriver\Remote\RemoteWebDriver;
use Facebook\WebDriver\WebDriverBy;

use Behat\Behat\Context\BehatContext,
  Behat\Behat\Exception\PendingException;
use Behat\Gherkin\Node\PyStringNode,
    Behat\Gherkin\Node\TableNode;

class BrowserStackFeatureContext extends BrowserStackContext {
  /** @Given /^I am on "([^"]*)"$/ */
  public function iAmOnSite($url) {
    self::$driver->get($url);
  }

  /** @When /^I search for "([^"]*)"$/ */
  public function iSearchFor($searchText) {
    $element = self::$driver->findElement(WebDriverBy::name("q"));
    $element->sendKeys($searchText);
    $element->submit();
    sleep(5);
  }

  /** @Then /^I get title as "([^"]*)"$/ */
  public function iShouldGet($string) {
    $title = self::$driver->getTitle();
    if ((string)  $string !== $title) {
      throw new Exception("Expected title: '". $string. "'' Actual is: '". $title. "'");
    }
  }

  /** @Then /^I should see "([^"]*)"$/ */
  public function iShouldSee($string) {
    $source = self::$driver->getPageSource();
    if (strpos($source, $string) === false) {
      throw new Exception("Expected to see: '". $string. "'' Actual is: '". $source. "'");
    }
  }
}
