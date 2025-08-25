Feature:
  I want to make sure that the system is allow to register an user

  Scenario: Command security:user:register is executed without errors
    Given I am the system
    When command "security:user:register" is executed with argument "username:an@email.com password:123"
    Then Is Executed Successfully
    Then I want to see "userId" in command output
    Then I want to see "username" in command output
    Then I want to see "an@email.com" in command output

  Scenario: I can register a user inside an account
    Given I am the system
    Given account "Minotour" with id "6666-6666-6666" exists
    When command "security:user:register" is executed with argument "username:an@email.com password:123 accountId:6666-6666-6666"
    Then Is Executed Successfully
    Then I want to see "userId" in command output
    Then I want to see "username" in command output
    Then I want to see "an@email.com" in command output
    Then I want to see "6666-6666-6666" in command output

  Scenario: As an admin, I can access to register new users
    Given user "admin@dediez.software" with role "ROLE_ADMIN" exists
    When I am logged as "admin@dediez.software"
    When I go to "/es/admin/users/register"
    Then the response status should be 200
    Then I see a form

  Scenario: As normal user I can't register new users
    Given user "admin@dediez.software" with role "ROLE_ADMIN" exists
    Given user "user1@dediez.software" with role "ROLE_USER" exists
    When I am logged as "user1@dediez.software"
    When I go to "/es/admin/users/register"
    Then the response status should be 403

  Scenario: As an admin, I can register new users
    Given user "admin@dediez.software" with role "ROLE_ADMIN" exists
    Given account "Minotour" with id "6666" exists
    When I am logged as "admin@dediez.software"
    When I go to "/es/admin/users/register"
    When I submit a form with "username:user@dediez.software|password:333|roles:[1,2]" and button "Registrar"
    When I follow redirect
    Then the response status should be 200
    Then I see a text "Usuario creado correctamente"
    Then I received an email with subject "Bienvenido user@dediez.software" to "user@dediez.software"

  Scenario: As owner I can register users to my account
      Given account "MyCompany" with id "666" exists
      Given user "owner@dediez.software" with role "ROLE_OWNER" of account "666" exists  
      When I am logged as "owner@dediez.software"
      When I go to "/es/users/register"
      Then the response status should be 200
  
  Scenario: As an admin, I can register new users by API
    Given user "admin@dediez.software" with role "ROLE_ADMIN" exists
    Given account "MyCompany" with id "123" exists
    When I am authenticated in API as "admin@dediez.software"
    When I send a POST request to "/api/users" with body:
    """
    {
      "username": "user@dediez.software",
      "account_id": "123"
    }
    """
    Then the status code should be 200
    And the response JSON should be similar to:
    """
    {
      "user_id": "1123-0136-456544465464-465446566",
      "username": "user@dediez.software",
      "account_id": "123"
    }
    """