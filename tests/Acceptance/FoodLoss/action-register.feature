Feature:
  I want to make sure that the software allow to register a action

  Scenario: As an admin, I can access to register new actions
    Given user "admin@dediez.software" with role "ROLE_ADMIN" exists
    When I am logged as "admin@dediez.software"
    When I go to "/es/admin/actions/register"
    Then the response status should be 200
    Then I see a form

  Scenario: As normal user I can't register new actions
    Given user "admin@dediez.software" with role "ROLE_ADMIN" exists
    Given user "user1@dediez.software" with role "ROLE_USER" exists
    When I am logged as "user1@dediez.software"
    When I go to "/es/admin/actions/register"
    Then the response status should be 403

  Scenario: As an admin, I can register new actions
    Given user "admin@dediez.software" with role "ROLE_ADMIN" exists
    When I am logged as "admin@dediez.software"
    When I go to "/es/admin/actions/register"
    When I submit a form with "description:Description" and button "Registrar"
    When I follow redirect
    Then the response status should be 200
    Then I see a text "Acción creada correctamente: Description"
  
  Scenario: As an admin, I can indicate its priority
    Given user "admin@dediez.software" with role "ROLE_ADMIN" exists
    When I am logged as "admin@dediez.software"
    When I go to "/es/admin/actions/register"
    When I submit a form with "description:This is a new action|priority:0" and button "Registrar"
    When I follow redirect
    Then the response status should be 200
    Then I see a text "Acción creada correctamente: This is a new action"