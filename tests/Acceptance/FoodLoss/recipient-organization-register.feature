Feature:
  I want to make sure that the software allows to register an entidad receptora

  Scenario: As an owner, I can access to register new entidades receptoras
    Given account "MyCompany" with id "mycompany" exists
    Given user "owner@dediez.software" with role "ROLE_OWNER" of account "mycompany" exists
    When I am logged as "owner@dediez.software"
    When I go to "/es/recipient-organizations/register"
    Then the response status should be 200
    Then I see a form

  Scenario: As an admin, I can not access to register new entidades receptoras
    Given user "admin@dediez.software" with role "ROLE_ADMIN" exists
    When I am logged as "admin@dediez.software"
    When I go to "/es/recipient-organizations/register"
    Then the response status should be 403

  Scenario: As a normal user, I can not register new entidades receptoras
    Given user "user1@dediez.software" with role "ROLE_USER" exists
    When I am logged as "user1@dediez.software"
    When I go to "/es/recipient-organizations/register"
    Then the response status should be 403

  Scenario: As an owner, I can register new entidades receptoras
    Given account "MyCompany" with id "mycompany" exists
    Given user "owner@dediez.software" with role "ROLE_OWNER" of account "mycompany" exists
    When I am logged as "owner@dediez.software"
    When I go to "/es/recipient-organizations/register"
    When I submit a form with "name:Test Entidad Receptora" and button "Registrar"
    When I follow redirect
    Then the response status should be 200
    Then I see a text "Entidad receptora creada correctamente: Test Entidad Receptora"
