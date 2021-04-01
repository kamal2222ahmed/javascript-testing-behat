# javascript-testing-behat
Example of JavaScript testing with Behat

# Instructions

1. Clone this repository.
2. Install dependencies `composer install`
3. Start Chrome/Chromium in `remote debugging mode` at port `9222`. If a different port is used, update `behat.yml` accordingly.
4. Execute the tests `./vendor/bin/behat`

To start Chrome/Chromium in headless mode:

`google-chrome --disable-gpu --headless --remote-debugging-address=0.0.0.0 --remote-debugging-port=9222`

To start Chrome/Chromium showing the browser window:

`google-chrome --remote-debugging-address=0.0.0.0 --remote-debugging-port=9222`

You might get an error if you do not start Chrome/Chromium in headless mode and you already had a browser window opened. 
If so, close any browser windon and try again. The error you get is similar to:

`Could not fetch version information from http://127.0.0.1:9222/json/version. Please check if Chrome is running. 
Please see docs/troubleshooting.md if Chrome crashed unexpected. (RuntimeException)`

ref: https://pantheon.io/blog/javascript-testing-behat

Nowadays, many web interfaces use JavaScript to enhance user interactions, for example, providing autocomplete widgets in search forms. Let’s see how to use Behat to test this JavaScript functionality. Behat is a PHP framework for automated testing. With the help of libraries like Mink and MinkExtension, it can be used for testing web sites and applications.

The example will visit Wikipedia’s homepage and interact with the autocomplete feature of the search bar.

Project setup
First, create a folder to contain the project’s files and install the needed packages:

mkdir -p ~/projects/javascript-behat-tests
cd ~/projects/javascript-behat-tests
composer require --dev behat/behat behat/mink-extension behat/mink-goutte-driver dmore/behat-chrome-extension

Now, initialize Behat and create a configuration file.

./vendor/bin/behat --init
touch behat.yml # creates empty configuration file

The content of the configuration file should be as follows:

default:
  suites:
    default:
      contexts:
        - FeatureContext
        - Behat\MinkExtension\Context\MinkContext

  extensions:
    Behat\MinkExtension:
      base_url: https://en.wikipedia.org/
      goutte: ~

Refer to this guide for more information on the setup procedure. Note that the previous configuration file will not let us test JavaScript features, yet. More to come later.

Writing a simple Behat test
When working with multiple libraries, I recommend trying a simple implementation to make sure things are working as expected. This helps isolate issues as you keep adding more tools to the stack. Let’s write a simple Behat test to make sure things are working. For now, JavaScript functionality will not be tested.

After running the `behat --init` command before, there should be a `features` folder where test files can be placed. Inside that folder, create a file called search.feature.

touch features/search.feature

The file should contain the following:

Feature: Search
  In order to find articles to read
  As a site user
  I need to be able to use the search bar

  Scenario: Search for a term
    Given I am on the homepage
    When I fill in "search" with "pantheon"
    And I press "searchButton"
    Then I should see "Pantheon (software), a web development platform"
    And I should see "Pantheon (desktop environment), a GTK+-based desktop environment"

The test will visit Wikipedia’s homepage as defined in the `base_url` setting for the MinkExtension in `behat.yml`. Then, it will enter the term `pantheon` in the search bar at the top right of the page and click the search button. Finally, it will check if the next page contains texts that would be part of the search results.

To run the tests, execute the behat command with no parameters.

./vendor/bin/behat

The tests should pass and a summary like the following should be printed at the end:

1 scenario (1 passed)
5 steps (5 passed)
0m1.07s (11.55Mb)

A glimpse at Behat, Mink, MinkExtension, and GoutteDriver
Let’s step back a bit and analyze what we have done so far and what role each of the packages play. Behat will parse the `search.feature` file looking for scenarios that contain our test cases. Each scenario is a collection of steps that represent the actions a user would take while interacting with the website. Steps are written in Gherkin, a human readable language, and start with keywords like `Given`, `When`, `Then`, `And`, and `But`. These steps are matched by Behat to step definitions which are PHP functions with instructions to interact with the website. For example, the step `Given I am on the homepage` is match to the following step definition:

public function iAmOnHomepage()
{
    $this->visitPath('/');
}

For every step used in a scenario, Behat will look for a matching step definition which is a custom PHP function with specific PHP annotations. If no matching function is found, you will get an error indicating that there are undefined steps. In this example, we did not write any PHP code; how is it working?

The steps used in the example are provided by the MinkExtension via the MinkContext. This context was added to the project as indicated in the `behat.yml`. It comes with many steps and step definitions for common tasks like visiting urls, clicking links, pressing buttons, filling out form elements, and asserting expected text appears on the site. These, and others, are actions that a user would do via the browser while interacting with a website. Does that mean that we are using a real browser under the hood to run the tests? Sometimes.

The MinkExtension is actually an integration layer between Behat and Mink. Mink itself is another independent package that offers a unified interface for emulating and controlling browsers via drivers. In `behat.yml`, under the MinkExtension section, `goutte: ~` indicates that we are enabling the GoutteDriver. Goutte is a command line browser emulator that uses curl to make HTTP requests to a website and to fake user interactions. When enabled, this is the default driver that will be used for the tests, unless otherwise specified. It is important to note that Goutte does not support JavaScript.

In short, we are using Behat to execute tests written in Gherkin. These tests consist of a series of steps and step definitions provided by the MinkExtension. The latter uses GoutteDriver to emulate a user interacting with a website. JavaScript cannot yet be tested because the command line browser emulator cannot read or execute JavaScript.

Testing JavaScript-enabled features
So, what do we need to do in order to test JavaScript-enabled features? You need a Mink driver that can control a browser with JavaScript support. The Selenium2 driver is a popular choice because it can be used to control:

Many types of browsers, such as Firefox and Chrome

Multiple browsers at the same time to execute tests in parallel

Remote browsers, such as inside a virtual machine or a totally different computer

This flexibility comes with some overhead. The biggest one is that you need Selenium Standalone Server, which itself requires Java. Adding another language to your stack might not be something you could do in every project. A lighter alternative is to use the Chrome driver, which lets you control Google Chrome either in headless mode or showing the user interface. We are also going to use the Behat Chrome Extension.

Tagging scenarios with @javascript
Whether you use the Selenium2 or Chrome drivers, you need to tag your scenario with the `@javascript` tag. This informs Behat that we want to use a driver with JavaScript support. Let’s create a new test scenario in the `search.feature` file created before:

Feature: Search
  #...

  Scenario: Search for a term
    # ...

  @javascript
  Scenario: Search for a term using autocomplete
    Given I am on the homepage
    When I select the first autocomplete option for "behat computer" on the "search" field
    And I press the search button
    Then first header of the page should be "Behat (computer science)"
    And I should see "Behat is a test framework for behavior-driven development written in the PHP programming language."

This scenario includes custom steps. Explaining how to write them is beyond the scope of this article, but you still need to have them available in your code base for the example to work. Replace the `features/bootstrap/FeatureContext.php` file that was created when Behat was initialized, by this file from the example repository. There you can see the code behind the custom steps. If you type the code manually, note that the `FeatureContext` class extends `RawMinkContext`. The part that I want to highlight is where we interact with JavaScript.

// Based on code by Lyle Mantooth.
// @see https://gist.github.com/IslandUsurper/12723643dddc9315ff71
$this->getSession()->wait(1000, 'jQuery(".suggestions").is(":visible") === true');

$xpath = $element->getXpath();
// Down key.
$driver->keyDown($xpath, 40);
$driver->keyUp($xpath, 40);

The `wait` command is only available for JavaScript-enabled drivers. The method receives 2 parameters, a timeout and a JavaScript expression. The latter will be evaluated until it is TRUE or the timeout (expressed in milliseconds) is reached. Note that jQuery is used as part of the expression. jQuery does not come with Behat, Mink, ChromeDriver, or any of our test packages; however, jQuery is globally available in the Wikipedia website and that is why we can use it here. Any globally available library or core JavaScript feature can be used as part of the expression.

You also need to understand how the site works in order to adequately write step definitions. Wikipedia has a `div` tag with a `suggestion` class that becomes visible when the search autocomplete is triggered. We are using that to wait for the autocomplete to appear and then press the down key. In this case, we are triggering separate `keyDown` and `keyUp` because this is the expectation from the site. By instructing the down key should be pressed, the first element of the autocomplete is selected and the text is copied to the search bar.

When writing this custom step, you need to understand how your website or application works in terms of JavaScript behavior and events. For example, Drupal’s autocomplete also binds its behavior to key down/up events, not key press events. See this step definition as an example of autocomplete selection in Drupal. If you are using a framework like React, it is possible to change the value of form elements, while the application state is not updated. That may lead to unexpected behavior and failures in the test.

Configuring behat.yml for JavaScript testing
The behat.yml configuration will be slightly different depending on which driver you want to use. The example below is for the ChromeDriver:

default:
  suites:
    default:
      contexts:
          - FeatureContext
          - Behat\MinkExtension\Context\MinkContext

  extensions:
    DMore\ChromeExtension\Behat\ServiceContainer\ChromeExtension: ~
    Behat\MinkExtension:
      base_url: https://en.wikipedia.org/
      goutte: ~
      browser_name: chrome
      default_session: command_line_browser
      javascript_session: js_enabled_browser
      sessions:
          command_line_browser:
         goutte: ~
        js_enabled_browser:
       chrome:
         api_url: http://localhost:9222
         validate_certificate: false

This configuration file has 3 important parts. First, the ChromeExtension is enabled. Second, the MinkExtension is configured to use the Goutte driver by default. Third, the Chrome driver is used for JavaScript-enabled tests (tagged with `@javascript`). Note that the Chrome driver is expected to connect to Chrome/Chromium in the same computer (localhost) on port 9222.

Start Chrome in Debugging mode
The last thing to do before runnig the tests is to start Chrome or Chromium in debugging mode. The exact command to run will depend on your operating system and whether you are using Chrome or Chromium. In any case, you need to find the browser binary and, from the command line, execute it passing some flags. The examples below assume a `google-chrome` binary available in your PATH environment variable. For possible locations of the browser executable, have a look at this reference.

If you want to run the browser in headless mode, execute:

google-chrome --disable-gpu --headless --remote-debugging-address=0.0.0.0 --remote-debugging-port=9222

If you want to show the browser’s interface, execute:

google-chrome --remote-debugging-address=0.0.0.0 --remote-debugging-port=9222

In both cases, you should see a message like this:

DevTools listening on ws://0.0.0.0:9222/devtools/browser/[uuid]

Note that if you decide to run Chrome showing the browser’s interface, you might have to close any open browser window before running the command or you might get an error like this:

Could not fetch version information from http://127.0.0.1:9222/json/version. Please check if Chrome is running. Please see docs/troubleshooting.md if Chrome crashed unexpected. (RuntimeException)

Finally, execute the `behat` command with no parameters:

./vendor/bin/behat

This would execute the two scenarios we created in this blog post. If you only want to execute only the scenarios that are tagged with `@javascript` run:

./vendor/bin/behat --tags=@javascript

If you only want to execute only the scenarios that are not tagged with `@javascript` run:

./vendor/bin/behat --tags=~@javascript

You can find the code for this demo in this repository.

Conclusion
There are many ways to test JavaScript-enabled features. Using ChromeDriver is a light way that does not require having Java to run a Selenium server. In either case, you need to understand your website or application to write proper step definitions. Pay particular attention to key event bindings and state updates for React applications. Also, check if there are packages that provide integration between your platform and Behat since that will simplify writing tests. For example, there are extensions for Drupal, WordPress, Symfony, and Laravel with custom steps for common tasks performed in those platforms like logging in or creating content.

Behat can be used as part of a continuous integration workflow to make sure code changes do not break existing features. These tests also help in scoping features. Once the tests that describe the user interaction pass, we know that this task is done. Testing for JavaScript, in particular, can help detect bugs as big as a missing script breaking the entire site or as small as an interactive form element not working as expected. If you want to learn more, make sure to check out my talk at WordCamp US 2019.


