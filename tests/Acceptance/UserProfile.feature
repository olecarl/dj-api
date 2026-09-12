Feature: View my own user profile
  As an authenticated user
  I want to view my own profile through a dedicated endpoint
  So that I do not need to know or provide my user ID

  Background:
    Given a user exists with email "user@example.com"
    And another user exists with email "other@example.com"

  Scenario: An authenticated user can view their own profile
    Given I am authenticated as "user@example.com"
    When I send a GET request to "/me"
    Then the response status code should be 200
    And the response should contain the profile for "user@example.com"
    And the response should contain the user's ID
    And the response should contain the user's roles
    And the response should not contain a password

  Scenario: The profile belongs to the authenticated user
    Given I am authenticated as "user@example.com"
    When I send a GET request to "/me"
    Then the response should not contain the profile for "other@example.com"

  Scenario: An administrator can view their own profile
    Given an administrator exists with email "admin@example.com"
    And I am authenticated as "admin@example.com"
    When I send a GET request to "/me"
    Then the response status code should be 200
    And the response should contain the profile for "admin@example.com"

  Scenario: An anonymous client cannot view a profile
    When I send a GET request to "/me"
    Then the response status code should be 401

  Scenario: A user ID cannot be supplied in the path
    Given I am authenticated as "user@example.com"
    When I send a GET request to "/me/00000000-0000-4000-8000-000000000000"
    Then the response status code should be 404

  Scenario: A query parameter cannot select another user
    Given I am authenticated as "user@example.com"
    When I send a GET request to "/me?userId=00000000-0000-4000-8000-000000000000"
    Then the response status code should be 200
    And the response should contain the profile for "user@example.com"
    And the response should not contain the profile for "other@example.com"

  Scenario Outline: The profile keeps the canonical user identity in hypermedia formats
    Given I am authenticated as "user@example.com"
    And I request the response as "<content_type>"
    When I send a GET request to "/me"
    Then the response status code should be 200
    And the response self link should be "/users/<user_id>"

    Examples:
      | content_type                 |
      | application/ld+json          |
      | application/hal+json         |
      | application/vnd.api+json     |
