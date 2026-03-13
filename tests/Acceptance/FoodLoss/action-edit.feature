Feature:
  I want to make sure that the system is allow to access the details of a action

  Scenario: As an admin, I can access to edit actions of my account
    Given user "admin@dediez.software" with role "ROLE_ADMIN" exists
    Given action "Action 1" with id "333" exists
    When I am logged as "admin@dediez.software"
    When I go to "/es/admin/actions/edit/333"
    When I submit a form with "description:A description|priority:0" and button "Guardar"
    When I follow redirect
    Then the response status should be 200
    Then I see a text "Acción actualizada con éxito"

  Scenario: As an owner, I can't access to edit actions
    Given account "MyCompany" with id "mycompany" exists
    Given user "owner@dediez.software" with role "ROLE_OWNER" of account "mycompany" exists
    Given action "An action" with id "333" exists
    When I am logged as "owner@dediez.software"
    When I go to "/es/admin/actions/edit/333"
    Then the response status should be 403