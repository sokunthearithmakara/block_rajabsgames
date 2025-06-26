@block @block_rajabsgames
Feature: Basic tests for Rajab's Games

  @javascript
  Scenario: Plugin block_rajabsgames appears in the list of installed additional plugins
    Given I log in as "admin"
    When I navigate to "Plugins > Plugins overview" in site administration
    And I follow "Additional plugins"
    Then I should see "Rajab's Games"
    And I should see "block_rajabsgames"
