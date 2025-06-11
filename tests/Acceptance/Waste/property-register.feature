Feature:
  I want to make sure that the software allow to register a property

  Scenario: As an admin, I can access to register new properties
    Given user "admin@dediez.software" with role "ROLE_ADMIN" exists
    When I am logged as "admin@dediez.software"
    When I go to "/es/admin/properties/register"
    Then the response status should be 200
    Then I see a form

  Scenario: As normal user I can't register new properties
    Given user "admin@dediez.software" with role "ROLE_ADMIN" exists
    Given user "user1@dediez.software" with role "ROLE_USER" exists
    When I am logged as "user1@dediez.software"
    When I go to "/es/admin/properties/register"
    Then the response status should be 403

  Scenario: As an admin, I can register new properties
    Given user "admin@dediez.software" with role "ROLE_ADMIN" exists
    When I am logged as "admin@dediez.software"
    When I go to "/es/admin/properties/register"
    When I submit a form with "name:Hotel" and button "Registrar"
    When I follow redirect
    Then the response status should be 200
    Then I see a text "Propiedad creada correctamente"

  Scenario: As an owner, I can register new properties for my account
    Given account "MyCompany" with id "666" exists
    Given user "owner@dediez.software" with role "ROLE_OWNER" of account "666" exists
    When I am logged as "owner@dediez.software"
    When I go to "/es/properties/register"
    When I submit a form with "name:Hotel" and button "Registrar"
    When I follow redirect
    Then the response status should be 200
    Then I see a text "Propiedad creada correctamente"