Feature:
  I want to make sure that the software allow to register a delivery log

  Scenario: As an owner, I can access to register new delivery logs
    Given account "MyAccount" with id "my-account" exists
    Given user "owner@dediez.software" with role "ROLE_OWNER" of account "my-account" exists
    When I am logged as "owner@dediez.software"
    When I go to "/es/delivery-logs/register"
    Then the response status should be 200
    Then I see a form

  Scenario: As an admin, I can't register new delivery logs
    Given user "admin@dediez.software" with role "ROLE_ADMIN" exists
    When I am logged as "admin@dediez.software"
    When I go to "/es/delivery-logs/register"
    Then the response status should be 403

  Scenario: As an owner, I can register a delivery log
    Given account "MyAccount" with id "my-account" exists
    Given user "owner@dediez.software" with role "ROLE_OWNER" of account "my-account" exists
    Given property "MyProperty" with id "prop-1" of account "my-account" exists
    Given recipient organization "FoodBank" with id "bank-1" of account "my-account" exists
    When I am logged as "owner@dediez.software"
    When I go to "/es/delivery-logs/register"
    When I submit a form with "date:2026-04-06|quantity:10|unit:kg|productType:Bananas|recipientOrganizationId:bank-1|propertyId:prop-1" and button "Registrar"
    When I follow redirect
    Then the response status should be 200
    Then I see a text "Registro de entrega creado correctamente: Bananas"
