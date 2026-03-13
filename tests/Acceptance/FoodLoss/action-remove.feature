Feature:
  I want to make sure that the system is allow to remove a action

  Scenario: As an admin, I can access to remove actions
    Given user "admin@dediez.software" with role "ROLE_ADMIN" exists
    Given action "Action 1" with id "333" exists
    When I am logged as "admin@dediez.software"
    When I go to "/es/admin/actions/remove/333"
    When I follow redirect
    Then the response status should be 200
    Then I see a text "Se ha eliminado la acción Action 1"

  Scenario: As normal user I can't remove actions
    Given user "admin@dediez.software" with role "ROLE_ADMIN" exists
    Given user "user1@dediez.software" with role "ROLE_USER" exists
    Given action "Action 1" with id "333" exists
    When I am logged as "user1@dediez.software"
    When I go to "/es/admin/actions/remove/333"
    Then the response status should be 403