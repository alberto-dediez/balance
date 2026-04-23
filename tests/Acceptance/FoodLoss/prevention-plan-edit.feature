Feature:
  I want to make sure that the system is allow to access the details of a prevention plan

  Scenario: As an admin, I can't access to edit prevention plans
    Given user "admin@dediez.software" with role "ROLE_ADMIN" exists
    Given prevention plan "Plan 1" with id "333" exists
    When I am logged as "admin@dediez.software"
    When I go to "/es/prevention-plans/edit/333"
    Then the response status should be 403

  Scenario: As an owner, I can edit my prevention plans and return to the list
    Given account "MyCompany" with id "mycompany" exists
    Given user "owner@dediez.software" with role "ROLE_OWNER" of account "mycompany" exists
    Given prevention plan "Plan 1" with id "333" of account "mycompany" exists
    Given property "Property 1" with id "prop1" of account "mycompany" exists
    When I am logged as "owner@dediez.software"
    When I go to "/es/prevention-plans/edit/333"
    Then the response status should be 200
    When I submit a form with "name:Plan Actualizado|properties:[prop1]" and button "Guardar y salir"
    Then I am redirected to "/es/prevention-plans"
    Then I see a text "Plan de prevención actualizado con éxito"

  Scenario: As an owner, I can edit my prevention plans and continue to actions
    Given account "MyCompany" with id "mycompany" exists
    Given user "owner@dediez.software" with role "ROLE_OWNER" of account "mycompany" exists
    Given prevention plan "Plan 1" with id "333" of account "mycompany" exists
    Given property "Property 1" with id "prop1" of account "mycompany" exists
    When I am logged as "owner@dediez.software"
    When I go to "/es/prevention-plans/edit/333"
    Then the response status should be 200
    When I submit a form with "name:Plan Actualizado|properties:[prop1]" and button "Continuar"
    Then I am redirected to "/es/prevention-plans/edit/333/validate"
    Then I see a text "Plan de prevención actualizado con éxito"

  Scenario: As an owner, I cannot access to edit prevention plans of other account
    Given account "MyCompany" with id "mycompany" exists
    Given account "OtherCompany" with id "othercompany" exists
    Given user "owner@dediez.software" with role "ROLE_OWNER" of account "mycompany" exists
    Given prevention plan "Plan 1" with id "333" of account "othercompany" exists
    When I am logged as "owner@dediez.software"
    When I go to "/es/prevention-plans/edit/333"
    Then the response status should be 403

  Scenario: As normal user I can't access to edit prevention plans
    Given account "MyCompany" with id "mycompany" exists
    Given user "user1@dediez.software" with role "ROLE_USER" of account "mycompany" exists
    Given prevention plan "Plan 1" with id "333" of account "mycompany" exists
    When I am logged as "user1@dediez.software"
    When I go to "/es/prevention-plans/edit/333"
    Then the response status should be 403
