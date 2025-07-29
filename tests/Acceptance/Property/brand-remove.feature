Feature:
  I want to make sure that the system is allow to remove a brand

  Scenario: As an admin, I can access to remove brands
    Given user "admin@dediez.software" with role "ROLE_ADMIN" exists
    Given brand "Orizonia" with id "123" exists
    When I am logged as "admin@dediez.software"
    When I go to "/es/admin/brands/remove/123"
    When I follow redirect
    Then the response status should be 200
    Then I see a text "Se ha eliminado la marca"

  Scenario: As normal user I can't remove brands
    Given user "admin@dediez.software" with role "ROLE_ADMIN" exists
    Given user "user1@dediez.software" with role "ROLE_USER" exists
    Given brand "Orizonia" with id "123" exists
    When I am logged as "user1@dediez.software"
    When I go to "/es/admin/brands/remove/123"
    Then the response status should be 403