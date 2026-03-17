Feature:
  I want to make sure that the system is allow to remove a service category

  Scenario: As an admin, I can access to remove service categories
    Given user "admin@dediez.software" with role "ROLE_ADMIN" exists
    Given service category "Service category 1" with id "333" exists
    When I am logged as "admin@dediez.software"
    When I go to "/es/admin/service-categories/remove/333"
    When I follow redirect
    Then the response status should be 200
    Then I see a text "Se ha eliminado la categoría de servicio Service category 1"

  Scenario: As normal user I can't remove service categories
    Given user "admin@dediez.software" with role "ROLE_ADMIN" exists
    Given user "user1@dediez.software" with role "ROLE_USER" exists
    Given service category "Service category 1" with id "333" exists
    When I am logged as "user1@dediez.software"
    When I go to "/es/admin/service-categories/remove/333"
    Then the response status should be 403