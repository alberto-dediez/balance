Feature:
  I want to make sure that the software allow to register a packaging

  Scenario: As an owner, I can access to register new packagings
    Given account "MyCompany" with id "mycompany" exists
    Given user "owner@dediez.software" with role "ROLE_OWNER" of account "mycompany" exists
    When I am logged as "owner@dediez.software"
    When I go to "/es/packagings/register"
    Then the response status should be 200
    Then I see a form

  Scenario: As admin I can't register new packagings
    Given user "admin@dediez.software" with role "ROLE_ADMIN" exists
    When I am logged as "admin@dediez.software"
    When I go to "/es/packagings/register"
    Then the response status should be 403

  Scenario: As an owner, I can register new packagings
    Given account "MyCompany" with id "mycompany" exists
    Given user "owner@dediez.software" with role "ROLE_OWNER" of account "mycompany" exists
    When I am logged as "owner@dediez.software"
    When I go to "/es/packagings/register"
    When I submit a form with "name:Test Envase|packagingType:0" and button "Registrar"
    When I follow redirect
    Then the response status should be 200
    Then I see a text "Envase creado correctamente: Test Envase"
