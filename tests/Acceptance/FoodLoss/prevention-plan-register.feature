Feature:
  I want to make sure that the software allow to register a prevention plan

  Scenario: As an admin, I can't access to register new prevention plans
    Given user "admin@dediez.software" with role "ROLE_ADMIN" exists
    When I am logged as "admin@dediez.software"
    When I go to "/es/prevention-plans/register"
    Then the response status should be 403

  Scenario: As an owner, I can register a new prevention plan
    Given account "MyCompany" with id "mycompany" exists
    Given user "owner@dediez.software" with role "ROLE_OWNER" of account "mycompany" exists
    When I am logged as "owner@dediez.software"
    When I go to "/es/prevention-plans/register"
    Then the response status should be 200
    Then I see a form
    When I submit a form with "name:Plan de Prevención Test" and button "Registrar"
    When I follow redirect
    Then the response status should be 200
    Then I see a text "Plan de prevención creado correctamente: Plan de Prevención Test"

  Scenario: As normal user I can't register new prevention plans
    Given account "MyCompany" with id "mycompany" exists
    Given user "user1@dediez.software" with role "ROLE_USER" of account "mycompany" exists
    When I am logged as "user1@dediez.software"
    When I go to "/es/prevention-plans/register"
    Then the response status should be 403
